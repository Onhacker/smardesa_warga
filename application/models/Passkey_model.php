<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Passkey/WebAuthn and local PIN support for the warga PWA.
 *
 * The browser/OS keeps the private biometric key.  This model only stores the
 * public credential and verifies signed WebAuthn assertions on the server.
 */
class Passkey_model extends CI_Model
{
    private $lastError = '';
    private $rpId = NULL;

    public function error()
    {
        return $this->lastError;
    }

    public function available()
    {
        return !warga_demo_mode()
            && warga_database_available()
            && class_exists('lbuchs\\WebAuthn\\WebAuthn')
            && $this->db->table_exists('warga_passkey_credentials')
            && $this->db->table_exists('warga_login_tokens')
            && $this->db->field_exists('login_pin_hash', 'users')
            && $this->db->field_exists('session_version', 'users')
            && $this->db->field_exists('session_version', 'warga_login_tokens');
    }

    public function rp_id()
    {
        if ($this->rpId !== NULL) return $this->rpId;
        $configured = trim((string) getenv('WARGA_WEBAUTHN_RP_ID'));
        if ($configured !== '') {
            $configured = strtolower(trim($configured));
            $configured = preg_replace('/:\d+$/', '', $configured);
            if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $configured)) {
                return $this->rpId = $configured;
            }
        }
        $parts = parse_url(trim((string) getenv('APP_URL')));
        $host = is_array($parts) && !empty($parts['host']) ? strtolower(trim((string) $parts['host'])) : '';
        $host = preg_replace('/:\d+$/', '', $host);
        if ($host === '') {
            $host = strtolower(trim((string) (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')));
            $host = preg_replace('/:\d+$/', '', $host);
        }
        return $this->rpId = $host !== '' ? $host : 'localhost';
    }

    public function rp_name()
    {
        $name = trim((string) (getenv('WARGA_WEBAUTHN_RP_NAME') ?: ''));
        if ($name !== '') return function_exists('mb_substr') ? mb_substr($name, 0, 120, 'UTF-8') : substr($name, 0, 120);
        return function_exists('warga_system_name') ? warga_system_name() : 'SI DAPULIK';
    }

    private function webauthn()
    {
        if (!class_exists('lbuchs\\WebAuthn\\WebAuthn')) {
            throw new RuntimeException('Library Passkey belum tersedia di server.');
        }
        // Accept the platform attestation formats emitted by Chrome/Android,
        // iOS, and ordinary browsers. We still require user verification and
        // validate the client origin below; limiting this to `none` would make
        // the installed Android TWA reject devices that return `android-key`.
        $webauthn = new lbuchs\WebAuthn\WebAuthn($this->rp_name(), $this->rp_id(), NULL, TRUE);
        $allowed = $this->android_key_hashes();
        if ($allowed) $webauthn->addAndroidKeyHashes($allowed);
        return $webauthn;
    }

    private function android_key_hashes()
    {
        $allowed = array();
        $hashes = trim((string) getenv('WARGA_WEBAUTHN_ANDROID_KEY_HASHES'));
        if ($hashes !== '') {
            foreach (preg_split('/[,\s]+/', $hashes) as $hash) {
                $hash = trim((string) $hash);
                if ($hash !== '' && preg_match('/^[A-Za-z0-9+\/_=-]{20,100}$/', $hash)) $allowed[] = $hash;
            }
        }

        // Reuse Android signing certificates already approved for this TWA.
        // The WebAuthn Android origin contains the URL-safe base64 form of the
        // same SHA-256 certificate bytes stored as colon-hex in assetlinks.
        $assetlinksPath = defined('FCPATH') ? FCPATH . '.well-known/assetlinks.json' : '';
        if ($assetlinksPath !== '' && is_file($assetlinksPath) && is_readable($assetlinksPath) && filesize($assetlinksPath) <= 65536) {
            $contents = file_get_contents($assetlinksPath);
            $statements = $contents === FALSE ? array() : json_decode((string) $contents, TRUE);
            if (is_array($statements)) {
                foreach ($statements as $statement) {
                    $target = is_array($statement) && isset($statement['target']) && is_array($statement['target']) ? $statement['target'] : array();
                    $relations = is_array($statement) && isset($statement['relation']) && is_array($statement['relation']) ? $statement['relation'] : array();
                    $package = trim((string) (getenv('WARGA_WEBAUTHN_ANDROID_PACKAGE') ?: 'id.co.mediaverse.smartkampung'));
                    if (($target['namespace'] ?? '') !== 'android_app'
                        || ($target['package_name'] ?? '') !== $package
                        || !in_array('delegate_permission/common.get_login_creds', $relations, TRUE)
                        || empty($target['sha256_cert_fingerprints'])
                        || !is_array($target['sha256_cert_fingerprints'])) continue;
                    foreach ($target['sha256_cert_fingerprints'] as $fingerprint) {
                        $hex = preg_replace('/[^A-Fa-f0-9]/', '', (string) $fingerprint);
                        if (strlen($hex) !== 64) continue;
                        $binary = hex2bin($hex);
                        if ($binary !== FALSE) $allowed[] = rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
                    }
                }
            }
        }
        return array_values(array_unique($allowed));
    }

    /** Apply an exact RP/domain boundary before the library verifies it again. */
    private function valid_client_origin($clientDataJson)
    {
        $client = json_decode((string) $clientDataJson, TRUE);
        $origin = is_array($client) ? trim((string) ($client['origin'] ?? '')) : '';
        if ($origin === '') return FALSE;
        $androidPrefix = 'android:apk-key-hash:';
        if (strpos($origin, $androidPrefix) === 0) {
            $hash = substr($origin, strlen($androidPrefix));
            return $hash !== '' && in_array($hash, $this->android_key_hashes(), TRUE);
        }
        $parts = parse_url($origin);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return FALSE;
        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $rpId = strtolower(rtrim($this->rp_id(), '.'));
        if ($rpId === 'localhost') {
            return in_array($scheme, array('http', 'https'), TRUE) && $host === 'localhost';
        }
        if ($scheme !== 'https') return FALSE;
        return $host === $rpId || (strlen($host) > strlen($rpId) && substr($host, -strlen('.' . $rpId)) === '.' . $rpId);
    }

    private function base64url_encode($value)
    {
        return rtrim(strtr(base64_encode((string) $value), '+/', '-_'), '=');
    }

    private function base64url_decode($value)
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) return FALSE;
        $padding = strlen($value) % 4;
        if ($padding) $value .= str_repeat('=', 4 - $padding);
        $decoded = base64_decode(strtr($value, '-_', '+/'), TRUE);
        return $decoded === FALSE ? FALSE : $decoded;
    }

    private function buffer_binary($value)
    {
        if (is_string($value)) return $value;
        if (is_object($value) && method_exists($value, 'getBinaryString')) return $value->getBinaryString();
        return '';
    }

    private function user_query()
    {
        return $this->db->select('u.*, r.name AS role_name, r.slug AS role_slug, v.village_code, v.name AS village_name, v.district_name, v.regency_code, v.regency_name')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->join('village_tenants v', 'v.id = u.village_id', 'left')
            ->where('u.is_active', 1);
    }

    private function tenant_allows($regencyCode)
    {
        $tenant = function_exists('warga_tenant_code') ? warga_tenant_code('') : '';
        $tenant = function_exists('warga_normalize_tenant_code') ? warga_normalize_tenant_code($tenant, '') : strtoupper(trim((string) $tenant));
        if ($tenant === '' || strtolower($tenant) === 'default') return TRUE;
        $code = function_exists('warga_normalize_tenant_code') ? warga_normalize_tenant_code($regencyCode, '') : strtoupper(trim((string) $regencyCode));
        return $code !== '' && hash_equals($tenant, $code);
    }

    public function user_for_identity($identity)
    {
        if (!warga_database_available()) return NULL;
        $identity = trim((string) $identity);
        if ($identity === '' || strlen($identity) > 180) return NULL;
        $user = $this->user_query()->group_start()
            ->where('u.username', $identity)
            ->or_where('u.email', $identity)
            ->or_where('u.phone', $identity)
            ->group_end()->limit(1)->get()->row_array();
        if (!$user || !warga_role_is_allowed($user['role_slug'] ?? '') || !$this->tenant_allows($user['regency_code'] ?? '')) return NULL;
        return $user;
    }

    public function user_by_id($userId)
    {
        if (!warga_database_available()) return NULL;
        $user = $this->user_query()->where('u.id', (int) $userId)->limit(1)->get()->row_array();
        if (!$user || !warga_role_is_allowed($user['role_slug'] ?? '') || !$this->tenant_allows($user['regency_code'] ?? '')) return NULL;
        return $user;
    }

    private function user_bytes($userId)
    {
        return hash('sha256', 'sidapulik-webauthn-user:' . (int) $userId, TRUE);
    }

    private function pin_pepper($pin)
    {
        $CI =& get_instance();
        $key = is_object($CI) && property_exists($CI, 'config') ? (string) $CI->config->item('encryption_key') : '';
        return hash_hmac('sha256', (string) $pin, $key !== '' ? $key : 'sidapulik-pin');
    }

    private function challenge_state($action, $userId, $challenge)
    {
        return array(
            'action' => (string) $action,
            'user_id' => (int) $userId,
            'challenge' => $this->base64url_encode($challenge),
            'created_at' => time()
        );
    }

    private function valid_challenge($action, $userId)
    {
        $state = $this->session->userdata('warga_passkey_challenge');
        if (!is_array($state)
            || (string) ($state['action'] ?? '') !== (string) $action
            || (int) ($state['user_id'] ?? 0) !== (int) $userId
            || empty($state['challenge'])
            || (int) ($state['created_at'] ?? 0) < time() - 300) {
            return FALSE;
        }
        $challenge = $this->base64url_decode($state['challenge']);
        return is_string($challenge) && strlen($challenge) >= 16 ? array($state, $challenge) : FALSE;
    }

    public function status($userId)
    {
        $passkeys = array();
        if (warga_database_available() && $this->db->table_exists('warga_passkey_credentials')) {
            $passkeys = $this->db->select('id, label, created_at, last_used_at')
                ->where(array('user_id' => (int) $userId, 'revoked_at' => NULL))
                ->order_by('created_at', 'DESC')->get('warga_passkey_credentials')->result_array();
        }
        $pinEnabled = FALSE;
        if (warga_database_available() && $this->db->field_exists('login_pin_hash', 'users')) {
            $row = $this->db->select('login_pin_hash')->where('id', (int) $userId)->limit(1)->get('users')->row_array();
            $pinEnabled = is_array($row) && trim((string) ($row['login_pin_hash'] ?? '')) !== '';
        }
        return array('available' => $this->available(), 'pin_enabled' => $pinEnabled, 'passkeys' => $passkeys);
    }

    public function registration_options(array $user)
    {
        $this->lastError = '';
        if (!$this->available()) return array('success' => FALSE, 'message' => 'Login biometrik belum tersedia pada server.');
        try {
            $existing = $this->db->select('credential_id')->where(array('user_id' => (int) $user['id'], 'revoked_at' => NULL))->get('warga_passkey_credentials')->result_array();
            $ids = array();
            foreach ($existing as $row) {
                $decoded = $this->base64url_decode($row['credential_id'] ?? '');
                if ($decoded !== FALSE) $ids[] = $decoded;
            }
            $username = trim((string) ($user['email'] ?? '')) ?: trim((string) ($user['username'] ?? ''));
            if ($username === '') $username = 'warga-' . (int) $user['id'];
            $displayName = trim((string) ($user['name'] ?? 'Warga')) ?: 'Warga';
            $webauthn = $this->webauthn();
            // Resident credentials are required so a registered device can
            // authenticate without asking the user to type an email/phone.
            $options = $webauthn->getCreateArgs($this->user_bytes($user['id']), $username, $displayName, 240, 'required', 'required', FALSE, $ids);
            $this->session->set_userdata('warga_passkey_challenge', $this->challenge_state('register', $user['id'], $this->buffer_binary($webauthn->getChallenge())));
            return array('success' => TRUE, 'options' => $options);
        } catch (Throwable $exception) {
            log_message('error', 'Passkey registration options failed: ' . $exception->getMessage());
            return array('success' => FALSE, 'message' => 'Login biometrik belum dapat disiapkan.');
        }
    }

    public function verify_registration($userId, array $payload)
    {
        $this->lastError = '';
        $valid = $this->valid_challenge('register', $userId);
        if ($valid === FALSE) return array('success' => FALSE, 'message' => 'Sesi pendaftaran biometrik sudah kedaluwarsa. Ulangi dari pengaturan akun.');
        $response = isset($payload['response']) && is_array($payload['response']) ? $payload['response'] : array();
        $clientData = $this->base64url_decode($response['clientDataJSON'] ?? '');
        $attestation = $this->base64url_decode($response['attestationObject'] ?? '');
        if ($clientData === FALSE || $attestation === FALSE || strlen($clientData) < 2 || strlen($attestation) < 2) return array('success' => FALSE, 'message' => 'Respons perangkat tidak valid.');
        $this->session->unset_userdata('warga_passkey_challenge');
        if (!$this->valid_client_origin($clientData)) return array('success' => FALSE, 'message' => 'Origin perangkat tidak diizinkan. Mulai ulang aktivasi biometrik.');
        try {
            $webauthn = $this->webauthn();
            $data = $webauthn->processCreate($clientData, $attestation, $valid[1], TRUE, TRUE, TRUE, FALSE);
            $credentialId = $this->buffer_binary($data->credentialId ?? '');
            $publicKey = (string) ($data->credentialPublicKey ?? '');
            if ($credentialId === '' || $publicKey === '') throw new RuntimeException('Credential kosong.');
            $encodedId = $this->base64url_encode($credentialId);
            if (strlen($encodedId) > 1024) throw new RuntimeException('Credential terlalu panjang.');
            if ($this->db->where('credential_id', $encodedId)->count_all_results('warga_passkey_credentials') > 0) return array('success' => FALSE, 'message' => 'Perangkat ini sudah terdaftar.');
            $label = trim((string) ($payload['label'] ?? 'Perangkat ini'));
            $label = substr(preg_replace('/[^\p{L}\p{N} ._()\-]/u', '', $label), 0, 120);
            if ($label === '') $label = 'Perangkat ini';
            $aaguid = $this->buffer_binary($data->AAGUID ?? '');
            $ok = $this->db->insert('warga_passkey_credentials', array(
                'user_id' => (int) $userId,
                'credential_id' => $encodedId,
                'public_key_pem' => $publicKey,
                'signature_counter' => max(0, (int) ($data->signatureCounter ?? 0)),
                'aaguid' => $aaguid !== '' ? bin2hex($aaguid) : NULL,
                'label' => $label,
                'transports' => 'internal',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ));
            return $ok ? array('success' => TRUE, 'message' => 'Login biometrik berhasil diaktifkan.') : array('success' => FALSE, 'message' => 'Credential belum dapat disimpan.');
        } catch (Throwable $exception) {
            log_message('error', 'Passkey registration verification failed: ' . $exception->getMessage());
            return array('success' => FALSE, 'message' => 'Verifikasi perangkat gagal. Pastikan biometrik atau PIN perangkat sudah aktif.');
        }
    }

    public function login_options($identity)
    {
        $this->lastError = '';
        if (!$this->available()) return array('success' => FALSE, 'message' => 'Login biometrik belum tersedia.');
        $identity = trim((string) $identity);
        $user = $identity !== '' ? $this->user_for_identity($identity) : NULL;
        if ($identity !== '' && !$user) return array('success' => FALSE, 'message' => 'Akun atau login biometrik tidak ditemukan.');
        $credentials = $identity !== ''
            ? $this->db->select('credential_id')->where(array('user_id' => (int) $user['id'], 'revoked_at' => NULL))->get('warga_passkey_credentials')->result_array()
            : array();
        // An empty allowCredentials list invokes the discoverable credential
        // flow. Let the authenticator decide whether this device has a usable
        // credential; querying global credential counts would disclose server
        // state and adds work without improving authentication safety.
        if ($identity !== '' && !$credentials) return array('success' => FALSE, 'message' => 'Akun atau login biometrik tidak ditemukan.');
        try {
            $ids = array();
            foreach ($credentials as $row) {
                $decoded = $this->base64url_decode($row['credential_id'] ?? '');
                if ($decoded !== FALSE) $ids[] = $decoded;
            }
            if ($identity !== '' && !$ids) return array('success' => FALSE, 'message' => 'Credential perangkat tidak valid.');
            $webauthn = $this->webauthn();
            $options = $webauthn->getGetArgs($ids, 240, FALSE, FALSE, FALSE, TRUE, TRUE, 'required');
            $state = $this->challenge_state('login', $user ? $user['id'] : 0, $this->buffer_binary($webauthn->getChallenge()));
            $state['identity'] = substr($identity, 0, 180);
            $this->session->set_userdata('warga_passkey_challenge', $state);
            $this->session->set_userdata('warga_passkey_identity', $state['identity']);
            return array('success' => TRUE, 'options' => $options);
        } catch (Throwable $exception) {
            log_message('error', 'Passkey login options failed: ' . $exception->getMessage());
            return array('success' => FALSE, 'message' => 'Login biometrik belum dapat disiapkan.');
        }
    }

    public function verify_login(array $payload)
    {
        $state = $this->session->userdata('warga_passkey_challenge');
        if (!is_array($state) || (string) ($state['action'] ?? '') !== 'login'
            || !array_key_exists('user_id', $state) || (int) ($state['user_id'] ?? -1) < 0
            || empty($state['challenge']) || (int) ($state['created_at'] ?? 0) < time() - 300) {
            return array('success' => FALSE, 'message' => 'Sesi login biometrik sudah kedaluwarsa.');
        }
        $challenge = $this->base64url_decode($state['challenge'] ?? '');
        if ($challenge === FALSE) return array('success' => FALSE, 'message' => 'Sesi login biometrik tidak valid.');
        $response = isset($payload['response']) && is_array($payload['response']) ? $payload['response'] : array();
        $clientData = $this->base64url_decode($response['clientDataJSON'] ?? '');
        $authenticatorData = $this->base64url_decode($response['authenticatorData'] ?? '');
        $signature = $this->base64url_decode($response['signature'] ?? '');
        $rawId = $payload['rawId'] ?? ($payload['id'] ?? '');
        $credentialId = $this->base64url_decode($rawId);
        if ($clientData === FALSE || $authenticatorData === FALSE || $signature === FALSE || $credentialId === FALSE) return array('success' => FALSE, 'message' => 'Respons perangkat tidak valid.');
        $this->session->unset_userdata('warga_passkey_challenge');
        if (!$this->valid_client_origin($clientData)) return array('success' => FALSE, 'message' => 'Origin perangkat tidak diizinkan. Mulai ulang login biometrik.');
        $encodedId = $this->base64url_encode($credentialId);
        $credentialQuery = $this->db->where(array('credential_id' => $encodedId, 'revoked_at' => NULL));
        if ((int) $state['user_id'] > 0) $credentialQuery->where('user_id', (int) $state['user_id']);
        $credential = $credentialQuery->limit(1)->get('warga_passkey_credentials')->row_array();
        if (!$credential) return array('success' => FALSE, 'message' => 'Credential perangkat tidak dikenali.');
        $user = $this->user_by_id((int) $credential['user_id']);
        if (!$user) return array('success' => FALSE, 'message' => 'Akun tidak aktif.');
        $userHandle = $response['userHandle'] ?? '';
        if ($userHandle !== '') {
            $decodedHandle = $this->base64url_decode($userHandle);
            if ($decodedHandle === FALSE || !hash_equals($this->user_bytes($user['id']), $decodedHandle)) return array('success' => FALSE, 'message' => 'Akun perangkat tidak sesuai.');
        }
        try {
            $webauthn = $this->webauthn();
            $webauthn->processGet($clientData, $authenticatorData, $signature, (string) $credential['public_key_pem'], $challenge, (int) $credential['signature_counter'], TRUE, TRUE);
            $newCounter = max(0, (int) $webauthn->getSignatureCounter());
            $updates = array('last_used_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'));
            if ($newCounter > (int) $credential['signature_counter']) $updates['signature_counter'] = $newCounter;
            $this->db->where('id', (int) $credential['id'])->update('warga_passkey_credentials', $updates);
            $this->session->unset_userdata('warga_passkey_identity');
            return $user ? array('success' => TRUE, 'user' => $user, 'identity' => (string) ($state['identity'] ?? '')) : array('success' => FALSE, 'message' => 'Akun tidak aktif.');
        } catch (Throwable $exception) {
            log_message('error', 'Passkey login verification failed: ' . $exception->getMessage());
            return array('success' => FALSE, 'message' => 'Verifikasi biometrik gagal. Coba lagi atau gunakan PIN/kata sandi.');
        }
    }

    public function set_pin($userId, $pin)
    {
        if (!$this->available()) return array('success' => FALSE, 'message' => 'Login PIN belum tersedia pada server.');
        $pin = trim((string) $pin);
        if (!preg_match('/^[0-9]{6}$/', $pin)) return array('success' => FALSE, 'message' => 'PIN harus terdiri dari 6 angka.');
        if (preg_match('/^(\d)\1{5}$/', $pin) || in_array($pin, array('012345', '123456', '234567', '345678', '456789', '987654', '876543', '765432', '654321'), TRUE)) {
            return array('success' => FALSE, 'message' => 'Gunakan PIN yang tidak berulang atau berurutan agar lebih aman.');
        }
        // A six-digit PIN has intentionally low entropy. Pepper it with the
        // application key before password_hash so a database-only leak cannot
        // be cracked by enumerating one million values offline.
        $hash = password_hash($this->pin_pepper($pin), PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') return array('success' => FALSE, 'message' => 'PIN belum dapat disimpan.');
        $ok = $this->db->where('id', (int) $userId)->update('users', array('login_pin_hash' => $hash, 'login_pin_failed_count' => 0, 'login_pin_locked_until' => NULL, 'updated_at' => date('Y-m-d H:i:s')));
        return $ok ? array('success' => TRUE, 'message' => 'PIN login berhasil diaktifkan.') : array('success' => FALSE, 'message' => 'PIN belum dapat disimpan.');
    }

    public function disable_pin($userId)
    {
        if (!$this->available()) return array('success' => FALSE, 'message' => 'Login PIN belum tersedia pada server.');
        $ok = $this->db->where('id', (int) $userId)->update('users', array('login_pin_hash' => NULL, 'login_pin_failed_count' => 0, 'login_pin_locked_until' => NULL, 'updated_at' => date('Y-m-d H:i:s')));
        return $ok ? array('success' => TRUE, 'message' => 'PIN login dinonaktifkan.') : array('success' => FALSE, 'message' => 'PIN belum dapat dinonaktifkan.');
    }

    public function verify_pin($identity, $pin, $boundUser = NULL)
    {
        if (!$this->available()) return array('success' => FALSE, 'message' => 'Login PIN belum tersedia pada server.');
        $identity = trim((string) $identity);
        if ($identity === '') {
            // The HttpOnly binding identifies the account, while the PIN
            // remains the proof of possession. Never use the cookie alone to
            // create a logged-in session.
            if (is_array($boundUser) && !empty($boundUser['id'])) {
                $user = $boundUser;
            } else {
                $this->load->model('Auth_model');
                $user = $this->Auth_model->pin_device_user();
            }
        } else {
            $user = $this->user_for_identity($identity);
        }
        if (!$user) {
            return array('success' => FALSE, 'message' => $identity === ''
                ? 'Perangkat ini belum terhubung ke akun PIN. Masuk sekali dengan email dan kata sandi, lalu aktifkan PIN pada menu Biometrik & PIN.'
                : 'Identitas atau PIN tidak sesuai.');
        }
        $this->db->trans_begin();
        // Lock the account row while reading/updating the failure counter so
        // concurrent guesses cannot bypass the five-attempt lockout.
        $row = $this->db->query('SELECT login_pin_hash, login_pin_failed_count, login_pin_locked_until FROM users WHERE id = ? LIMIT 1 FOR UPDATE', array((int) $user['id']))->row_array();
        $lockedUntil = strtotime((string) ($row['login_pin_locked_until'] ?? ''));
        if ($lockedUntil && $lockedUntil > time()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'PIN dikunci sementara. Coba lagi setelah ' . date('H:i', $lockedUntil) . '.');
        }
        $hash = (string) ($row['login_pin_hash'] ?? '');
        $peppered = $this->pin_pepper($pin);
        $verified = $hash !== '' && password_verify($peppered, $hash);
        // One release previously stored an unpeppered hash. Accept it once,
        // then upgrade it immediately after a successful verification.
        $legacy = !$verified && $hash !== '' && password_verify((string) $pin, $hash);
        if (!$verified && !$legacy) {
            $failed = ((int) ($row['login_pin_failed_count'] ?? 0)) + 1;
            $locked = $failed >= 5;
            $values = array('login_pin_failed_count' => $locked ? 0 : min(255, $failed), 'updated_at' => date('Y-m-d H:i:s'));
            if ($locked) $values['login_pin_locked_until'] = date('Y-m-d H:i:s', time() + 900);
            $this->db->where('id', (int) $user['id'])->update('users', $values);
            $this->db->trans_commit();
            return array('success' => FALSE, 'message' => $locked ? 'PIN dikunci 15 menit setelah terlalu banyak percobaan.' : 'Identitas atau PIN tidak sesuai.');
        }
        $updates = array('login_pin_failed_count' => 0, 'login_pin_locked_until' => NULL, 'last_login_at' => date('Y-m-d H:i:s'));
        if ($legacy) $updates['login_pin_hash'] = password_hash($peppered, PASSWORD_DEFAULT);
        $this->db->where('id', (int) $user['id'])->update('users', $updates);
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Login PIN belum dapat diproses. Coba lagi.');
        }
        $this->db->trans_commit();
        return array('success' => TRUE, 'user' => $user);
    }

    public function revoke($userId, $credentialId)
    {
        $ok = $this->db->where(array('id' => (int) $credentialId, 'user_id' => (int) $userId, 'revoked_at' => NULL))->update('warga_passkey_credentials', array('revoked_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')));
        return $ok ? array('success' => TRUE, 'message' => 'Perangkat berhasil dicabut.') : array('success' => FALSE, 'message' => 'Perangkat tidak ditemukan.');
    }
}
