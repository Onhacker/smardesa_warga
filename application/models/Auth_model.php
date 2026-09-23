<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model
{
    private $lastError = '';
    private $identity_schema_ready = false;
    private $session_version_available = NULL;
    private $registration_throttle_available = NULL;

    private function client_ip()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function login_identity_hash($identity)
    {
        $normalized = strtolower(trim((string) $identity));
        $normalized = preg_replace('/\s+/', '', $normalized);
        return hash_hmac('sha256', $normalized, (string) $this->config->item('encryption_key'));
    }

    private function is_login_throttled($identity)
    {
        if (warga_demo_mode() || !warga_database_available() || !$this->db->table_exists('login_failures')) return FALSE;
        $since = date('Y-m-d H:i:s', time() - 900);
        $identityCount = $this->db->where('identity_hash', $this->login_identity_hash($identity))->where('attempted_at >=', $since)->count_all_results('login_failures');
        $ipCount = $this->db->where('ip_address', $this->client_ip())->where('attempted_at >=', $since)->count_all_results('login_failures');
        return $identityCount >= 8 || $ipCount >= 30;
    }

    private function record_login_failure($identity)
    {
        if (warga_demo_mode() || !warga_database_available() || !$this->db->table_exists('login_failures')) return;
        if (mt_rand(1, 20) === 1) $this->db->where('attempted_at <', date('Y-m-d H:i:s', time() - 86400))->delete('login_failures');
        $this->db->insert('login_failures', array('identity_hash' => $this->login_identity_hash($identity), 'ip_address' => $this->client_ip()));
    }

    private function clear_login_failures($identity)
    {
        if (warga_demo_mode() || !warga_database_available() || !$this->db->table_exists('login_failures')) return;
        $this->db->where('identity_hash', $this->login_identity_hash($identity))->delete('login_failures');
    }

    /** Limited public wrappers for the Passkey/PIN controller. */
    public function login_is_throttled($identity)
    {
        return $this->is_login_throttled($identity);
    }

    public function note_login_failure($identity)
    {
        $this->record_login_failure($identity);
    }

    public function clear_login_failure($identity)
    {
        $this->clear_login_failures($identity);
    }

    private function session_version_ready()
    {
        if ($this->session_version_available !== NULL) return $this->session_version_available;
        if (warga_demo_mode() || !warga_database_available()) return $this->session_version_available = FALSE;
        return $this->session_version_available = (bool) $this->db->field_exists('session_version', 'users');
    }

    private function registration_throttle_ready()
    {
        if ($this->registration_throttle_available !== NULL) return $this->registration_throttle_available;
        if (warga_demo_mode() || !warga_database_available()) return $this->registration_throttle_available = FALSE;
        return $this->registration_throttle_available = (bool) $this->db->table_exists('registration_attempts');
    }

    private function registration_identity_hash($contact, $nik, $villageCode)
    {
        $contact = trim((string) $contact);
        // Treat formatting variants of one email/phone as one registration
        // identity, otherwise a client could evade the per-identity window by
        // adding spaces, punctuation, or different email casing.
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $contact = strtolower($contact);
        } else {
            $contact = preg_replace('/\D+/', '', $contact);
        }
        $identity = $contact . '|' . preg_replace('/\D+/', '', (string) $nik) . '|' . strtoupper(trim((string) $villageCode));
        return hash_hmac('sha256', $identity, (string) $this->config->item('encryption_key'));
    }

    private function registration_is_throttled($contact, $nik, $villageCode)
    {
        if (!$this->registration_throttle_ready()) return FALSE;
        $since = date('Y-m-d H:i:s', time() - 900);
        $identityHash = $this->registration_identity_hash($contact, $nik, $villageCode);
        $identityCount = $this->db->where('identity_hash', $identityHash)->where('attempted_at >=', $since)
            ->count_all_results('registration_attempts');
        $ipCount = $this->db->where('ip_address', $this->client_ip())->where('attempted_at >=', $since)
            ->count_all_results('registration_attempts');
        return $identityCount >= 5 || $ipCount >= 20;
    }

    private function record_registration_attempt($contact, $nik, $villageCode)
    {
        if (!$this->registration_throttle_ready()) return;
        if (mt_rand(1, 20) === 1) {
            $this->db->where('attempted_at <', date('Y-m-d H:i:s', time() - 86400))->delete('registration_attempts');
        }
        $this->db->insert('registration_attempts', array(
            'identity_hash' => $this->registration_identity_hash($contact, $nik, $villageCode),
            'ip_address' => $this->client_ip()
        ));
    }

    private function clear_registration_attempts($contact, $nik, $villageCode)
    {
        if (!$this->registration_throttle_ready()) return;
        $this->db->where('identity_hash', $this->registration_identity_hash($contact, $nik, $villageCode))
            ->delete('registration_attempts');
    }

    private function set_authenticated_session(array $user)
    {
        $data = array('warga_logged_in' => TRUE, 'warga_user_id' => (int) $user['id']);
        if ($this->session_version_ready()) $data['warga_session_version'] = max(1, (int) ($user['session_version'] ?? 1));
        $this->session->set_userdata($data);
    }

    /**
     * A normal CI session remains short-lived. Passkey/PIN sign-ins receive a
     * separate, revocable trusted-device cookie so returning users can
     * be restored for up to one year without making every anonymous session
     * immortal. Only a selector and a one-way token hash are stored server-side.
     */
    private function trusted_cookie_name()
    {
        return 'sdw_trusted_device';
    }

    private function trusted_cookie_value()
    {
        $name = $this->trusted_cookie_name();
        return isset($_COOKIE[$name]) ? trim((string) $_COOKIE[$name]) : '';
    }

    private function trusted_token_hash($secret)
    {
        return hash_hmac('sha256', (string) $secret, (string) $this->config->item('encryption_key'));
    }

    private function trusted_device_ttl()
    {
        // Keep the remembered-device window bounded even if a deployment
        // accidentally supplies an excessive value. The default is one year,
        // while a shorter value can be selected for stricter environments.
        $configured = (int) (getenv('WARGA_TRUSTED_DEVICE_TTL') ?: 31536000);
        return max(86400, min(31536000, $configured));
    }

    private function write_trusted_cookie($value, $expires)
    {
        $this->write_device_cookie($this->trusted_cookie_name(), $value, $expires);
    }

    /** Write one of the server-issued HttpOnly device cookies. */
    private function write_device_cookie($name, $value, $expires)
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string) $name);
        if ($name === '') return;
        $appUrl = parse_url(trim((string) getenv('APP_URL')));
        $secure = ENVIRONMENT === 'production'
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (is_array($appUrl) && strtolower((string) ($appUrl['scheme'] ?? '')) === 'https');
        $options = array(
            // Keep a past timestamp when revoking; clamping it to zero would
            // turn the deletion response into a new session cookie in some
            // browsers instead of replacing the one-year persistent cookie.
            'expires' => (int) $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => TRUE,
            'samesite' => 'Lax'
        );
        setcookie($name, (string) $value, $options);
        if ((int) $expires <= time()) unset($_COOKIE[$name]);
        else $_COOKIE[$name] = (string) $value;
    }

    private function pin_device_cookie_name()
    {
        return 'sdw_pin_device';
    }

    private function pin_device_cookie_value()
    {
        $name = $this->pin_device_cookie_name();
        return isset($_COOKIE[$name]) ? trim((string) $_COOKIE[$name]) : '';
    }

    /**
     * Issue a separate device binding for PIN login. It identifies the account
     * only; the PIN is still required for every sign-in. The binding survives
     * an ordinary sign-out so the account can be identified without email,
     * while PIN disable and session-version changes still invalidate it.
     */
    public function issue_pin_device_binding(array $user)
    {
        if (warga_demo_mode() || !warga_database_available() || !$this->db->table_exists('warga_login_tokens')
            || !$this->db->field_exists('session_version', 'users') || !$this->db->field_exists('session_version', 'warga_login_tokens')) return FALSE;
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) return FALSE;
        $version = max(1, (int) ($user['session_version'] ?? 1));
        // Rotate only the binding currently held by this browser; other
        // devices remain independently revocable and session-version bound.
        $this->revoke_pin_device_binding();
        $selector = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $expires = time() + $this->trusted_device_ttl();
        $this->db->where('user_id', $userId)->where('auth_method', 'pin_binding')
            ->where('expires_at <', date('Y-m-d H:i:s'))->delete('warga_login_tokens');
        $bindings = $this->db->select('selector')->where('user_id', $userId)
            ->where('auth_method', 'pin_binding')->where('revoked_at', NULL)
            ->order_by('created_at', 'DESC')->get('warga_login_tokens')->result_array();
        if (count($bindings) >= 8) {
            foreach (array_slice($bindings, 7) as $old) {
                if (!empty($old['selector'])) {
                    $this->db->where('selector', (string) $old['selector'])
                        ->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
                }
            }
        }
        $ok = $this->db->insert('warga_login_tokens', array(
            'selector' => $selector,
            'user_id' => $userId,
            'session_version' => $version,
            'token_hash' => $this->trusted_token_hash($secret),
            'auth_method' => 'pin_binding',
            'expires_at' => date('Y-m-d H:i:s', $expires),
            'last_used_at' => date('Y-m-d H:i:s'),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)
        ));
        if (!$ok || !$this->db->affected_rows()) return FALSE;
        $this->write_device_cookie($this->pin_device_cookie_name(), $selector . '.' . $secret, $expires);
        return TRUE;
    }

    /** Resolve the account bound to the PIN device cookie, without logging in. */
    public function pin_device_user()
    {
        if (warga_demo_mode() || !warga_database_available() || !$this->db->table_exists('warga_login_tokens')
            || !$this->db->field_exists('session_version', 'users') || !$this->db->field_exists('session_version', 'warga_login_tokens')) return NULL;
        $cookie = $this->pin_device_cookie_value();
        if (!preg_match('/^([A-Za-z0-9_-]{20,40})\.([A-Za-z0-9_-]{30,80})$/', $cookie, $match)) return NULL;
        $selector = $match[1];
        $secret = $match[2];
        $row = $this->db->select('t.*,u.id AS user_id,u.session_version AS current_session_version,u.name,u.username,u.email,u.phone,u.is_active,u.role_id,r.name AS role_name,r.slug AS role_slug,v.village_code,v.name AS village_name,v.district_name,v.regency_code,v.regency_name')
            ->from('warga_login_tokens t')
            ->join('users u', 'u.id=t.user_id')
            ->join('roles r', 'r.id=u.role_id')
            ->join('village_tenants v', 'v.id=u.village_id', 'left')
            ->where(array('t.selector' => $selector, 't.auth_method' => 'pin_binding', 't.revoked_at' => NULL))
            ->limit(1)->get()->row_array();
        $valid = is_array($row) && !empty($row['expires_at']) && strtotime((string) $row['expires_at']) > time()
            && (int) ($row['session_version'] ?? 1) === max(1, (int) ($row['current_session_version'] ?? 1))
            && hash_equals((string) ($row['token_hash'] ?? ''), $this->trusted_token_hash($secret))
            && (int) ($row['is_active'] ?? 0) === 1 && warga_role_is_allowed($row['role_slug'] ?? '');
        if ($valid && function_exists('warga_tenant_code')) {
            $tenant = warga_normalize_tenant_code(warga_tenant_code(''), '');
            $region = warga_normalize_tenant_code($row['regency_code'] ?? '', '');
            if ($tenant !== '' && strtolower($tenant) !== 'default' && ($region === '' || !hash_equals($tenant, $region))) $valid = FALSE;
        }
        if (!$valid) {
            if ($row) $this->db->where('selector', $selector)->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
            $this->write_device_cookie($this->pin_device_cookie_name(), '', time() - 3600);
            return NULL;
        }
        $this->db->where('selector', $selector)->update('warga_login_tokens', array('last_used_at' => date('Y-m-d H:i:s')));
        $row['id'] = (int) $row['user_id'];
        return $row;
    }

    public function revoke_pin_device_binding($userId = NULL)
    {
        $cookie = $this->pin_device_cookie_value();
        if (preg_match('/^([A-Za-z0-9_-]{20,40})\./', $cookie, $match)
            && warga_database_available() && $this->db->table_exists('warga_login_tokens')) {
            $this->db->where('selector', $match[1])->where('auth_method', 'pin_binding')->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
        }
        if ($userId !== NULL && warga_database_available() && $this->db->table_exists('warga_login_tokens')) {
            $this->db->where('user_id', (int) $userId)->where('auth_method', 'pin_binding')->where('revoked_at', NULL)->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
        }
        $this->write_device_cookie($this->pin_device_cookie_name(), '', time() - 3600);
    }

    /** Restore a trusted passkey/PIN device before current_user() is checked. */
    public function restore_trusted_session()
    {
        if ($this->session->userdata('warga_logged_in') || warga_demo_mode() || !warga_database_available()) return FALSE;
        $cookie = $this->trusted_cookie_value();
        // Avoid a metadata query for every anonymous request. Most visitors
        // have no remembered-device cookie at all.
        if ($cookie === '' || !$this->db->table_exists('warga_login_tokens')
            || !$this->db->field_exists('session_version', 'users')
            || !$this->db->field_exists('session_version', 'warga_login_tokens')) return FALSE;
        if (!preg_match('/^([A-Za-z0-9_-]{20,40})\.([A-Za-z0-9_-]{30,80})$/', $cookie, $match)) return FALSE;
        $selector = $match[1];
        $secret = $match[2];
        $row = $this->db->select('t.*,u.id AS user_id,u.session_version AS current_session_version,u.is_active,u.role_id,r.slug AS role_slug,v.regency_code')
            ->from('warga_login_tokens t')
            ->join('users u', 'u.id=t.user_id')
            ->join('roles r', 'r.id=u.role_id')
            ->join('village_tenants v', 'v.id=u.village_id', 'left')
            ->where(array('t.selector' => $selector, 't.revoked_at' => NULL))
            ->where_in('t.auth_method', array('passkey', 'pin'))
            ->limit(1)->get()->row_array();
        $expired = !$row || empty($row['expires_at']) || strtotime((string) $row['expires_at']) <= time();
        $valid = !$expired && (int) ($row['session_version'] ?? 1) === max(1, (int) ($row['current_session_version'] ?? 1))
            && hash_equals((string) $row['token_hash'], $this->trusted_token_hash($secret))
            && (int) ($row['is_active'] ?? 0) === 1
            && warga_role_is_allowed($row['role_slug'] ?? '');
        if ($valid && function_exists('warga_tenant_code')) {
            $tenant = warga_normalize_tenant_code(warga_tenant_code(''), '');
            $region = warga_normalize_tenant_code($row['regency_code'] ?? '', '');
            if ($tenant !== '' && strtolower($tenant) !== 'default' && ($region === '' || !hash_equals($tenant, $region))) $valid = FALSE;
        }
        if (!$valid) {
            if ($row) $this->db->where('selector', $selector)->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
            $this->write_trusted_cookie('', time() - 3600);
            return FALSE;
        }
        $this->session->sess_regenerate(TRUE);
        $this->session->set_userdata(array(
            'warga_logged_in' => TRUE,
            'warga_user_id' => (int) $row['user_id'],
            'warga_session_version' => max(1, (int) ($row['current_session_version'] ?? 1))
        ));
        // Keep the device secret stable during the token lifetime. Rotating it
        // on every request is unsafe for a PWA: several HTML/CSS/API requests
        // can arrive concurrently with the same cookie, and a later request
        // carrying the old value would otherwise revoke the freshly rotated
        // token. The selector is still individually revocable, the secret is
        // HttpOnly, and the session-version check invalidates every token after
        // a password/security change.
        $this->db->where('selector', $selector)->update('warga_login_tokens', array(
            'last_used_at' => date('Y-m-d H:i:s')
        ));
        return TRUE;
    }

    /** Create a one-year trusted-device token after a verified Passkey/PIN login. */
    public function authenticate_with_trusted_device(array $user, $method)
    {
        $this->session->sess_regenerate(TRUE);
        $this->set_authenticated_session($user);
        $this->issue_trusted_device((int) $user['id'], (string) $method, max(1, (int) ($user['session_version'] ?? 1)));
        if (warga_database_available() && isset($user['id'])) {
            $this->db->where('id', (int) $user['id'])->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
        }
    }

    private function issue_trusted_device($userId, $method, $sessionVersion = 1)
    {
        if (warga_demo_mode() || !warga_database_available() || !$this->db->table_exists('warga_login_tokens')) return FALSE;
        $method = in_array((string) $method, array('passkey', 'pin'), TRUE) ? (string) $method : 'passkey';
        $selector = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $expires = time() + $this->trusted_device_ttl();
        $this->db->where('user_id', (int) $userId)->where('expires_at <', date('Y-m-d H:i:s'))->delete('warga_login_tokens');
        $existing = $this->db->select('selector')->where('user_id', (int) $userId)
            ->where_in('auth_method', array('passkey', 'pin'))->where('revoked_at', NULL)
            ->order_by('created_at', 'DESC')->get('warga_login_tokens')->result_array();
        if (count($existing) >= 5) {
            foreach (array_slice($existing, 4) as $old) {
                if (!empty($old['selector'])) $this->db->where('selector', (string) $old['selector'])->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
            }
        }
        $this->db->insert('warga_login_tokens', array(
            'selector' => $selector,
            'user_id' => (int) $userId,
            'session_version' => max(1, (int) $sessionVersion),
            'token_hash' => $this->trusted_token_hash($secret),
            'auth_method' => $method,
            'expires_at' => date('Y-m-d H:i:s', $expires),
            'last_used_at' => date('Y-m-d H:i:s'),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)
        ));
        if (!$this->db->affected_rows()) return FALSE;
        $this->write_trusted_cookie($selector . '.' . $secret, $expires);
        return TRUE;
    }

    public function revoke_trusted_devices($userId, $method = NULL)
    {
        if (!warga_database_available() || !$this->db->table_exists('warga_login_tokens')) return;
        $this->db->where('user_id', (int) $userId)->where('revoked_at', NULL);
        if ($method !== NULL) $this->db->where('auth_method', (string) $method);
        $this->db->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
    }

    public function revoke_current_trusted_device()
    {
        $cookie = $this->trusted_cookie_value();
        if (preg_match('/^([A-Za-z0-9_-]{20,40})\./', $cookie, $match) && warga_database_available() && $this->db->table_exists('warga_login_tokens')) {
            $this->db->where('selector', $match[1])->update('warga_login_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
        }
        $this->write_trusted_cookie('', time() - 3600);
    }

    public function error()
    {
        return $this->lastError;
    }

    private function configured_tenant_code()
    {
        $code = function_exists('warga_tenant_code') ? warga_tenant_code('') : '';
        $code = function_exists('warga_normalize_tenant_code')
            ? warga_normalize_tenant_code($code, '')
            : strtoupper(trim((string) $code));
        return strtolower((string) $code) === 'default' ? '' : (string) $code;
    }

    private function branding_region_label($field, $fallback)
    {
        static $labels = array();
        $cacheKey = $field . '|' . $this->configured_tenant_code();
        if (isset($labels[$cacheKey])) return $labels[$cacheKey];

        $value = '';
        try {
            $this->load->model('Branding_model', 'authBranding');
            $tenantCode = $this->configured_tenant_code();
            $branding = $this->authBranding->current($tenantCode !== '' ? $tenantCode : warga_tenant_code('default'));
            $value = trim((string) ($branding[$field] ?? ''));
        } catch (Throwable $e) {
            $value = '';
        }
        if ($value === '') $value = $fallback;
        $value = function_exists('mb_convert_case')
            ? mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
            : ucwords(strtolower($value));
        return $labels[$cacheKey] = $value;
    }

    private function tenant_allows($regencyCode)
    {
        $tenantCode = $this->configured_tenant_code();
        if ($tenantCode === '') return TRUE;
        $regencyCode = function_exists('warga_normalize_tenant_code')
            ? warga_normalize_tenant_code($regencyCode, '')
            : strtoupper(trim((string) $regencyCode));
        return $regencyCode !== '' && hash_equals($tenantCode, $regencyCode);
    }

    /**
     * Daftar wilayah aktif untuk formulir pendaftaran warga.
     * Kode wilayah tetap berasal dari server; warga hanya memilih nama wilayah.
     */
    public function registration_regions()
    {
        $tenantCode = $this->configured_tenant_code();
        if (warga_demo_mode()) {
            if ($tenantCode !== '' && $tenantCode !== '95.01') return array();
            $institution = $this->branding_region_label('bentuk_lembaga', 'Desa');
            return array(array(
                'district_code' => '95.01.03',
                'district_name' => 'Asologaima',
                'village_code' => '95.01.03.2003',
                'village_name' => $institution . ' Araboda',
                'regency_code' => '95.01',
                'regency_name' => 'Jayawijaya',
                'province_name' => 'Papua Pegunungan'
            ));
        }

        if (!warga_database_available() || !$this->db->table_exists('village_tenants')) {
            return array();
        }

        $this->db
            ->select('district_code, district_name, village_code, name AS village_name, regency_code, regency_name, province_name')
            ->where('status', 'active');
        if ($tenantCode !== '') $this->db->where('regency_code', $tenantCode);
        return $this->db
            ->order_by('district_name', 'ASC')
            ->order_by('name', 'ASC')
            ->get('village_tenants')
            ->result_array();
    }

    private function demo_users()
    {
        $institution = $this->branding_region_label('bentuk_lembaga', 'Desa');
        $scope = array(
            'village_id' => '00000000-0000-4000-8000-000000000001',
            'village_code' => '95.01.03.2003',
            'village_name' => $institution . ' Araboda',
            'district_name' => 'Asologaima',
            'regency_code' => '95.01',
            'regency_name' => 'Jayawijaya',
            'institution' => $institution
        );
        return array(
            1 => array_merge($scope, array('id' => 1, 'role_id' => 1, 'role_slug' => 'warga', 'role_name' => 'Warga', 'name' => 'Yotam Wamena', 'username' => 'warga', 'email' => 'warga@demo.local', 'phone' => '081234567890')),
            2 => array_merge($scope, array('id' => 2, 'role_id' => 2, 'role_slug' => 'sekdes', 'role_name' => 'Sekretaris ' . $institution, 'name' => 'Markus Huby', 'username' => 'sekdes', 'email' => 'sekdes@demo.local', 'phone' => '081234567891')),
            3 => array_merge($scope, array('id' => 3, 'role_id' => 3, 'role_slug' => 'kepala-desa', 'role_name' => 'Kepala ' . $institution, 'name' => 'Yulius Wenda', 'username' => 'kades', 'email' => 'kades@demo.local', 'phone' => '081234567892'))
        );
    }

    private function demo_user($id)
    {
        $users = $this->demo_users();
        if (!isset($users[(int) $id])) return NULL;
        $overrides = $this->session->userdata('warga_demo_overrides');
        $override = is_array($overrides) && isset($overrides[(string) (int) $id]) && is_array($overrides[(string) (int) $id]) ? $overrides[(string) (int) $id] : array();
        return array_merge($users[(int) $id], array_intersect_key($override, array('email' => TRUE, 'phone' => TRUE)));
    }

    private function demo_password_hash($override)
    {
        if (is_array($override) && isset($override['password_hash']) && is_string($override['password_hash']) && $override['password_hash'] !== '') {
            return $override['password_hash'];
        }
        return password_hash('demo12345', PASSWORD_DEFAULT);
    }

    public function attempt($identity, $password)
    {
        $this->lastError = '';
        $identity = trim((string) $identity);
        if (warga_demo_mode()) {
            if (!$this->tenant_allows('95.01')) {
                $this->lastError = 'Akun demo tidak tersedia pada layanan kabupaten ini.';
                return FALSE;
            }
            $identity = strtolower($identity);
            foreach ($this->demo_users() as $user) {
                $effective = $this->demo_user((int) $user['id']);
                $overrides = $this->session->userdata('warga_demo_overrides');
                $override = is_array($overrides) && isset($overrides[(string) (int) $user['id']]) && is_array($overrides[(string) (int) $user['id']]) ? $overrides[(string) (int) $user['id']] : array();
                $demoPasswordHash = $this->demo_password_hash($override);
                if (password_verify((string) $password, $demoPasswordHash) && in_array($identity, array(strtolower($effective['username']), strtolower($effective['email']), strtolower($effective['phone'])), TRUE)) {
                    $this->session->sess_regenerate(TRUE);
                    $this->set_authenticated_session($user);
                    return $user;
                }
            }
            return FALSE;
        }

        if (!warga_database_available()) return FALSE;
        if ($this->is_login_throttled($identity)) {
            $this->lastError = 'Terlalu banyak percobaan masuk. Silakan tunggu 15 menit lalu coba lagi.';
            return FALSE;
        }
        $this->db->select('u.*, r.name AS role_name, r.slug AS role_slug, v.village_code, v.name AS village_name, v.district_name, v.regency_code, v.regency_name');
        $this->db->from('users u');
        $this->db->join('roles r', 'r.id = u.role_id');
        $this->db->join('village_tenants v', 'v.id = u.village_id', 'left');
        $this->db->where('u.is_active', 1);
        $this->db->group_start()->where('u.username', $identity)->or_where('u.email', $identity)->or_where('u.phone', $identity)->group_end();
        $user = $this->db->get()->row_array();
        if (!$user || !warga_role_is_allowed($user['role_slug'] ?? '') || !password_verify((string) $password, $user['password_hash'])) {
            $this->record_login_failure($identity);
            return FALSE;
        }
        if (!$this->tenant_allows(isset($user['regency_code']) ? $user['regency_code'] : '')) {
            $this->lastError = 'Akun ini terdaftar pada layanan kabupaten lain.';
            $this->record_login_failure($identity);
            return FALSE;
        }

        $this->clear_login_failures($identity);
        $this->session->sess_regenerate(TRUE);
        $this->set_authenticated_session($user);
        // A successful password login safely establishes which account owns
        // this browser. If the user already enabled PIN, bind this device so
        // the next PIN login can identify the account without an email field.
        if (!empty($user['login_pin_hash'])) $this->issue_pin_device_binding($user);
        $this->db->where('id', $user['id'])->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
        return $user;
    }

    public function current_user()
    {
        if (!$this->session->userdata('warga_logged_in') || !$this->session->userdata('warga_user_id')) return NULL;
        if (warga_demo_mode()) {
            $user = $this->demo_user((int) $this->session->userdata('warga_user_id'));
            if (!$user || !$this->tenant_allows(isset($user['regency_code']) ? $user['regency_code'] : '')) {
                $this->logout();
                return NULL;
            }
            return $user;
        }
        if (!warga_database_available()) return NULL;
        $this->ensure_identity_schema();
        $select = 'u.id,u.role_id,u.name,u.username,u.email,u.phone,u.is_active,u.last_login_at,u.village_id,r.name AS role_name,r.slug AS role_slug,v.village_code,v.name AS village_name,v.district_name,v.regency_code,v.regency_name';
        if ($this->session_version_ready()) $select .= ',u.session_version';
        $select .= ',cp.verification_status AS citizen_verification_status,cp.local_citizen_key';
        $this->db->select($select, FALSE)->from('users u')
            ->join('roles r', 'r.id=u.role_id')
            ->join('village_tenants v', 'v.id=u.village_id', 'left')
            ->join('citizen_profiles cp', 'cp.user_id=u.id', 'left');
        $user = $this->db
            ->where(array('u.id' => (int) $this->session->userdata('warga_user_id'), 'u.is_active' => 1))
            ->get()->row_array();
        if (!$user || !warga_role_is_allowed($user['role_slug'] ?? '')) {
            $this->logout();
            return NULL;
        }
        if (!$this->tenant_allows(isset($user['regency_code']) ? $user['regency_code'] : '')) {
            $this->logout();
            return NULL;
        }
        if ($this->session_version_ready()) {
            $currentVersion = max(1, (int) ($user['session_version'] ?? 1));
            $sessionVersion = $this->session->userdata('warga_session_version');
            if ($sessionVersion === NULL || $sessionVersion === '') {
                // A session created before migration 023 has no trustworthy
                // revocation epoch. Expire it once instead of adopting the
                // current value, otherwise a session that was revoked while
                // dormant could silently become valid again.
                $this->logout();
                return NULL;
            } elseif ((int) $sessionVersion !== $currentVersion) {
                $this->logout();
                return NULL;
            }
        }
        return $user;
    }

    private function normalize_profile_phone($value)
    {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (!preg_match('/^[0-9+() .-]+$/', $value)) return FALSE;
        $normalized = preg_replace('/[^0-9+]/', '', $value);
        return preg_match('/^\+?[0-9]{8,15}$/', $normalized) ? $normalized : FALSE;
    }

    public function update_contact($userId, $email, $phone)
    {
        $userId = (int) $userId;
        $email = strtolower(trim((string) $email));
        $phone = $this->normalize_profile_phone($phone);
        if ($email !== '' && (strlen($email) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL))) return array('success' => FALSE, 'message' => 'Email belum valid atau terlalu panjang.');
        if ($phone === FALSE) return array('success' => FALSE, 'message' => 'Nomor telepon harus berisi 8–15 digit angka.');
        if ($email === '' && $phone === '') return array('success' => FALSE, 'message' => 'Isi minimal email atau nomor telepon.');
        if (warga_demo_mode()) {
            $all = $this->session->userdata('warga_demo_overrides');
            if (!is_array($all)) $all = array();
            $key = (string) $userId;
            if (!isset($all[$key]) || !is_array($all[$key])) $all[$key] = array();
            foreach ($this->demo_users() as $candidate) {
                if ((int) $candidate['id'] === $userId) continue;
                $candidateEffective = $this->demo_user((int) $candidate['id']);
                if (($email !== '' && in_array($email, array(strtolower($candidateEffective['username']), strtolower($candidateEffective['email'])), TRUE)) || ($phone !== '' && $phone === $candidateEffective['phone'])) {
                    return array('success' => FALSE, 'message' => 'Email atau nomor telepon sudah digunakan akun lain.');
                }
            }
            $all[$key]['email'] = $email; $all[$key]['phone'] = $phone;
            $this->session->set_userdata('warga_demo_overrides', $all);
            return array('success' => TRUE);
        }
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database belum tersedia.');
        $this->db->group_start();
        if ($email !== '') $this->db->where('email', $email)->or_where('username', $email);
        if ($phone !== '') $this->db->or_where('phone', $phone)->or_where('username', $phone);
        $this->db->group_end()->where('id !=', $userId);
        if ($this->db->count_all_results('users') > 0) return array('success' => FALSE, 'message' => 'Email atau nomor telepon sudah digunakan akun lain.');
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try {
            $updated = $this->db->where('id', $userId)->update('users', array('email' => $email !== '' ? $email : NULL, 'phone' => $phone !== '' ? $phone : NULL, 'updated_at' => date('Y-m-d H:i:s')));
            $error = $this->db->error();
        } finally {
            $this->db->db_debug = $debug;
        }
        if ($updated) return array('success' => TRUE);
        return array('success' => FALSE, 'message' => ((int) $error['code'] === 1062) ? 'Email atau nomor telepon sudah digunakan akun lain.' : 'Data kontak belum dapat disimpan.');
    }

    public function change_password($userId, $currentPassword, $newPassword)
    {
        $userId = (int) $userId; $currentPassword = (string) $currentPassword; $newPassword = (string) $newPassword;
        if (strlen($newPassword) < 8 || strlen($newPassword) > 72 || strpos($newPassword, "\0") !== FALSE) return array('success' => FALSE, 'message' => 'Kata sandi baru harus 8–72 karakter tanpa karakter kosong.');
        if ($currentPassword === $newPassword) return array('success' => FALSE, 'message' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.');
        if (!$this->verify_password($userId, $currentPassword)) return array('success' => FALSE, 'message' => $this->error() ?: 'Kata sandi saat ini tidak sesuai.');
        if (warga_demo_mode()) {
            $all = $this->session->userdata('warga_demo_overrides');
            if (!is_array($all)) $all = array();
            if (!isset($all[(string) $userId]) || !is_array($all[(string) $userId])) $all[(string) $userId] = array();
            $all[(string) $userId]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            unset($all[(string) $userId]['password']);
            $this->session->set_userdata('warga_demo_overrides', $all);
            $this->session->sess_regenerate(TRUE);
            $this->session->set_userdata('warga_session_version', 1);
            return array('success' => TRUE);
        }
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database belum tersedia.');
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') return array('success' => FALSE, 'message' => 'Kata sandi belum dapat diubah.');
        $values = array('password_hash' => $hash, 'updated_at' => date('Y-m-d H:i:s'));
        if ($this->session_version_ready()) {
            $this->db->set('session_version', 'session_version + 1', FALSE);
        }
        $updated = $this->db->where('id', $userId)->update('users', $values);
        if ($updated) {
            $this->session->sess_regenerate(TRUE);
            if ($this->session_version_ready()) {
                $row = $this->db->select('session_version')->where('id', $userId)->limit(1)->get('users')->row_array();
                $this->session->set_userdata('warga_session_version', max(1, (int) ($row['session_version'] ?? 1)));
            }
        }
        return $updated ? array('success' => TRUE) : array('success' => FALSE, 'message' => 'Kata sandi belum dapat diubah.');
    }

    public function verify_password($userId, $password)
    {
        $this->lastError = '';
        $userId = (int) $userId; $password = (string) $password;
        if ($password === '') return FALSE;
        if (warga_demo_mode()) {
            $all = $this->session->userdata('warga_demo_overrides');
            $override = is_array($all) && isset($all[(string) $userId]) && is_array($all[(string) $userId]) ? $all[(string) $userId] : array();
            return password_verify($password, $this->demo_password_hash($override));
        }
        if (!warga_database_available()) return FALSE;
        $identity = 'account-reauth:' . $userId;
        if ($this->is_login_throttled($identity)) {
            $this->lastError = 'Terlalu banyak percobaan. Silakan tunggu 15 menit lalu coba lagi.';
            return FALSE;
        }
        $user = $this->db->select('password_hash')->where('id', $userId)->where('is_active', 1)->limit(1)->get('users')->row_array();
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $this->record_login_failure($identity);
            return FALSE;
        }
        $this->clear_login_failures($identity);
        return TRUE;
    }

    /**
     * Return the authenticated citizen's own profile details.
     *
     * Identity documents are deliberately not part of current_user(), which
     * is used throughout the application.  They are read only on the account
     * screen and decrypted after the user id has been matched to the current
     * session.  This keeps NIK/No. KK out of unrelated views and staff flows.
     */
    public function citizen_profile_for_user($userId)
    {
        $userId = (int) $userId;
        // CI_Model exposes loaded libraries through its magic __get(); using
        // isset($this->session) would therefore incorrectly reject a valid
        // session on production requests.
        $session = $this->session;
        if (!is_object($session) || !method_exists($session, 'userdata')) return NULL;
        $sessionUserId = (int) $session->userdata('warga_user_id');
        if ($userId < 1 || $sessionUserId < 1 || $userId !== $sessionUserId) return NULL;

        $empty = array(
            'nik' => '',
            'kk' => '',
            'birth_date' => '',
            'gender' => '',
            'address' => '',
            'verification_status' => 'unverified',
            'identity_stored' => FALSE,
            'identity_note' => ''
        );
        if (warga_demo_mode()) {
            $empty['nik'] = '950103••••••0001';
            $empty['kk'] = '950103••••••0001';
            $empty['birth_date'] = '1992-06-12';
            $empty['gender'] = 'Laki-laki';
            $empty['address'] = $this->branding_region_label('bentuk_lembaga', 'Desa') . ' Araboda, '
                . $this->branding_region_label('bentuk_kecamatan', 'Kecamatan') . ' Asologaima';
            $empty['verification_status'] = 'verified';
            $empty['identity_stored'] = TRUE;
            $empty['identity_note'] = 'Data identitas pada mode demo disamarkan.';
            return $empty;
        }
        if (!warga_database_available() || !$this->ensure_identity_schema()) {
            $empty['identity_note'] = 'Profil kependudukan belum tersedia pada server.';
            return $empty;
        }

        $this->db->select('cp.birth_date,cp.gender,cp.address_snapshot,cp.verification_status,cp.nik_encrypted,cp.kk_encrypted', FALSE)
            ->select('d.birth_date AS directory_birth_date,d.gender AS directory_gender', FALSE)
            ->from('citizen_profiles cp')
            ->join('village_resident_directory d', 'd.village_id=cp.village_id AND d.local_citizen_key=cp.local_citizen_key', 'left');
        $row = $this->db->where('cp.user_id', $userId)->limit(1)->get()->row_array();
        if (!$row) {
            $empty['identity_note'] = 'Profil kependudukan belum tersedia.';
            return $empty;
        }

        $profile = $empty;
        $profile['nik'] = isset($row['nik_encrypted']) ? $this->decrypt_identity($row['nik_encrypted']) : '';
        $profile['kk'] = isset($row['kk_encrypted']) ? $this->decrypt_identity($row['kk_encrypted']) : '';
        $profile['birth_date'] = !empty($row['birth_date']) ? (string) $row['birth_date']
            : (!empty($row['directory_birth_date']) ? (string) $row['directory_birth_date'] : '');
        $profile['gender'] = trim((string) (!empty($row['gender']) ? $row['gender']
            : (!empty($row['directory_gender']) ? $row['directory_gender'] : '')));
        $profile['address'] = trim((string) (isset($row['address_snapshot']) ? $row['address_snapshot'] : ''));
        $profile['verification_status'] = trim((string) (isset($row['verification_status']) ? $row['verification_status'] : 'unverified')) ?: 'unverified';
        $profile['identity_stored'] = $profile['nik'] !== '' || $profile['kk'] !== '';
        if (!$profile['identity_stored']) {
            $profile['identity_note'] = 'NIK dan No. KK belum tersimpan pada akun ini.';
        }
        return $profile;
    }

    public function register_citizen(array $data)
    {
        if (warga_demo_mode()) return array('success' => TRUE);
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database belum tersedia.');

        $name = trim((string) $data['name']);
        $legacyContact = trim((string) ($data['contact'] ?? ''));
        $emailInput = trim((string) ($data['email'] ?? ''));
        $phoneInput = trim((string) ($data['phone'] ?? ''));
        if ($emailInput === '' && filter_var($legacyContact, FILTER_VALIDATE_EMAIL)) $emailInput = $legacyContact;
        if ($phoneInput === '' && $legacyContact !== '' && !filter_var($legacyContact, FILTER_VALIDATE_EMAIL)) $phoneInput = $legacyContact;
        $nik = $this->identity_digits(isset($data['nik']) ? $data['nik'] : '');
        $kk = $this->identity_digits(isset($data['kk']) ? $data['kk'] : '');
        $districtCode = strtoupper(trim((string) (isset($data['district_code']) ? $data['district_code'] : '')));
        $villageCode = strtoupper(trim((string) $data['village_code']));
        $email = filter_var($emailInput, FILTER_VALIDATE_EMAIL) && strlen($emailInput) <= 160 ? strtolower($emailInput) : NULL;
        $phone = $this->normalize_profile_phone($phoneInput);
        $contactIdentity = strtolower((string) $email) . '|' . (string) $phone;
        if (!preg_match('/^[0-9]{16}$/', $nik) || !preg_match('/^[0-9]{16}$/', $kk)) {
            return array('success' => FALSE, 'message' => 'NIK dan No. KK harus terdiri dari 16 digit angka.');
        }
        if ($email === NULL) return array('success' => FALSE, 'message' => 'Masukkan alamat email yang valid untuk keamanan akun.');
        if ($phone === FALSE) return array('success' => FALSE, 'message' => 'Nomor telepon harus berisi 8–15 digit angka.');

        $village = $this->db->where('village_code', $villageCode)->where('status', 'active')->get('village_tenants')->row_array();
        if (!$village) return array('success' => FALSE, 'message' => 'Wilayah yang dipilih belum terdaftar atau tidak aktif.');
        if (!$this->tenant_allows(isset($village['regency_code']) ? $village['regency_code'] : '')) {
            return array('success' => FALSE, 'message' => 'Wilayah yang dipilih tidak termasuk layanan kabupaten ini.');
        }
        if ($districtCode !== '' && strtoupper(trim((string) $village['district_code'])) !== $districtCode) {
            return array('success' => FALSE, 'message' => 'Pilihan wilayah induk dan wilayah layanan tidak sesuai. Silakan pilih ulang.');
        }
        if ($this->registration_is_throttled($contactIdentity, $nik, $villageCode)) {
            return array('success' => FALSE, 'message' => 'Terlalu banyak percobaan pendaftaran. Silakan tunggu 15 menit lalu coba lagi.');
        }
        $this->record_registration_attempt($contactIdentity, $nik, $villageCode);
        $verification = $this->verify_resident_central($villageCode, $name, $nik, $kk);
        if (empty($verification['success'])) {
            return array('success' => FALSE, 'message' => isset($verification['message']) ? $verification['message'] : 'Data penduduk belum dapat diverifikasi.');
        }
        $resident = isset($verification['resident']) && is_array($verification['resident']) ? $verification['resident'] : array();
        $sourceKey = trim((string) (isset($resident['source_key']) ? $resident['source_key'] : ''));
        $canonicalName = trim((string) (isset($resident['display_name']) ? $resident['display_name'] : ''));
        if ($sourceKey === '' || $canonicalName === '') {
            return array('success' => FALSE, 'message' => 'Data penduduk belum dapat diverifikasi.');
        }
        if (!$this->ensure_identity_schema()) {
            return array('success' => FALSE, 'message' => 'Skema verifikasi penduduk belum siap pada server.');
        }
        $role = $this->db->where('slug', 'warga')->get('roles')->row_array();
        if (!$role) return array('success' => FALSE, 'message' => 'Peran warga belum disiapkan pada server.');

        // A NIK may have only one citizen account, regardless of the
        // village selected during registration. The database unique key below
        // remains the race-safe guard for simultaneous registrations.
        $profileNikHash = $this->profile_identity_hash($nik);
        $existingNikProfile = $this->db->where('nik_hash', $profileNikHash)
            ->limit(1)->get('citizen_profiles')->row_array();
        if ($existingNikProfile) {
            return array('success' => FALSE, 'message' => 'NIK ini sudah memiliki akun layanan warga. Silakan gunakan menu masuk.');
        }

        $this->db->group_start();
        $this->db->where('email', $email)->or_where('username', $email);
        if ($phone !== '') $this->db->or_where('phone', $phone)->or_where('username', $phone);
        $this->db->group_end();
        if ($this->db->count_all_results('users') > 0) return array('success' => FALSE, 'message' => 'Email atau nomor telepon sudah terdaftar.');

        $existingProfile = $this->db->where(array('village_id' => $village['id'], 'local_citizen_key' => $sourceKey))
            ->limit(1)->get('citizen_profiles')->row_array();
        if ($existingProfile) return array('success' => FALSE, 'message' => 'Penduduk ini sudah memiliki akun. Silakan gunakan menu masuk.');

        $username = 'warga_' . strtolower(substr(preg_replace('/[^a-z0-9]+/i', '', $canonicalName), 0, 20)) . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $now = date('Y-m-d H:i:s');
        if (!$this->db->trans_begin()) {
            return array('success' => FALSE, 'message' => 'Pendaftaran belum dapat dimulai. Silakan coba lagi.');
        }
        $inserted = $this->db->insert('users', array(
            'role_id' => (int) $role['id'],
            'village_id' => $village['id'],
            'name' => $canonicalName,
            'username' => $username,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : NULL,
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'is_active' => 1
        ));
        $userId = (int) $this->db->insert_id();
        if (!$inserted || $userId < 1) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Pendaftaran belum dapat disimpan.');
        }
        $profile = array(
            'id' => warga_uuid(),
            'user_id' => $userId,
            'village_id' => $village['id'],
            'local_citizen_key' => $sourceKey,
            'nik_hash' => $profileNikHash,
            'kk_hash' => $this->profile_identity_hash($kk),
            'name_hash' => $this->profile_identity_hash($this->identity_name($canonicalName)),
            'birth_date' => isset($resident['birth_date']) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', (string) $resident['birth_date']) ? $resident['birth_date'] : NULL,
            'gender' => isset($resident['gender']) ? trim((string) $resident['gender']) : NULL,
            'verification_status' => 'verified',
            'created_at' => $now,
            'updated_at' => $now
        );
        // Keep the documents available to the account owner without putting
        // plaintext identity values in the database. Required identity
        // columns are installed by the deployment migrations.
        $encryptedNik = $this->encrypt_identity($nik);
        $encryptedKk = $this->encrypt_identity($kk);
        if ($encryptedNik !== NULL) {
            $profile['nik_encrypted'] = $encryptedNik;
        }
        if ($encryptedKk !== NULL) {
            $profile['kk_encrypted'] = $encryptedKk;
        }
        if (!$this->db->insert('citizen_profiles', $profile) || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Profil penduduk belum dapat disimpan.');
        }
        if (!$this->db->trans_commit()) {
            return array('success' => FALSE, 'message' => 'Pendaftaran belum dapat diselesaikan. Silakan coba lagi.');
        }
        $this->clear_registration_attempts($contactIdentity, $nik, $villageCode);
        return array('success' => TRUE);
    }

    public function citizen_is_verified($userId, $villageId = '')
    {
        if (warga_demo_mode()) return TRUE;
        if (!warga_database_available() || !$this->ensure_identity_schema()) return FALSE;
        $this->db->select('cp.user_id AS verified_user_id', FALSE)
            ->from('citizen_profiles cp')
            ->join('users u', 'u.id=cp.user_id AND u.village_id=cp.village_id')
            ->join('village_tenants v', 'v.id=cp.village_id')
            ->join('village_resident_directory d', 'd.village_id=cp.village_id AND d.local_citizen_key=cp.local_citizen_key')
            ->where(array('cp.user_id' => (int) $userId, 'cp.verification_status' => 'verified',
                'u.is_active' => 1, 'v.status' => 'active', 'd.status' => 'active'));
        if (trim((string) $villageId) !== '') $this->db->where('cp.village_id', (string) $villageId);
        return (bool) $this->db->limit(1)->count_all_results();
    }

    private function ensure_identity_schema()
    {
        // Identity columns and unique indexes are installed by migrations
        // 007, 008, 012, and 018 during deployment. Never issue DDL from an
        // HTTP request; this guard is retained for existing call sites.
        if (!warga_database_available()) return FALSE;
        return $this->identity_schema_ready = TRUE;
    }

    private function verify_resident_central($villageCode, $name, $nik, $kk)
    {
        $base = rtrim(trim((string) getenv('WARGA_CENTRAL_API_URL')), '/');
        $url = $base . '/residents/verify';
        $parts = parse_url($url);
        if ($base === '' || !is_array($parts) || empty($parts['host'])
            || (ENVIRONMENT === 'production' && strtolower((string) (isset($parts['scheme']) ? $parts['scheme'] : '')) !== 'https')) {
            return array('success' => FALSE, 'message' => 'Layanan verifikasi warga belum dikonfigurasi.');
        }
        if (!function_exists('curl_init')) return array('success' => FALSE, 'message' => 'Layanan verifikasi warga belum tersedia pada server.');
        $body = json_encode(array('village_code' => $villageCode, 'name' => $name, 'nik' => $nik, 'kk' => $kk), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) return array('success' => FALSE, 'message' => 'Data verifikasi belum dapat diproses.');
        $timeout = (int) getenv('WARGA_CENTRAL_API_TIMEOUT');
        $timeout = max(5, min(30, $timeout > 0 ? $timeout : 15));
        $handle = curl_init($url);
        curl_setopt_array($handle, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTP_VERSION => defined('CURL_HTTP_VERSION_1_1') ? CURL_HTTP_VERSION_1_1 : 0,
            CURLOPT_HTTPHEADER => array('Accept: application/json', 'Content-Type: application/json', 'Cache-Control: no-store', 'Origin: ' . rtrim((string) getenv('APP_URL'), '/')),
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_SSL_VERIFYHOST => 2
        ));
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($response === FALSE || $error !== '') return array('success' => FALSE, 'message' => 'Layanan verifikasi warga sedang tidak dapat dihubungi. Coba lagi setelah koneksi tersedia.');
        $decoded = json_decode((string) $response, TRUE);
        if (!is_array($decoded)) return array('success' => FALSE, 'message' => 'Respons verifikasi warga tidak valid.');
        if ($status >= 200 && $status < 300 && !empty($decoded['success']) && isset($decoded['resident']) && is_array($decoded['resident'])) {
            return array('success' => TRUE, 'resident' => $decoded['resident']);
        }
        $code = isset($decoded['error']) ? (string) $decoded['error'] : '';
        if ($code === 'resident_directory_unavailable' || $code === 'service_unavailable') {
            return array('success' => FALSE, 'message' => 'Data penduduk wilayah belum tersinkron ke layanan warga. Silakan coba lagi setelah aplikasi desa terhubung ke internet.');
        }
        if ($code === 'rate_limited') return array('success' => FALSE, 'message' => 'Terlalu banyak percobaan. Silakan tunggu beberapa menit lalu coba lagi.');
        return array('success' => FALSE, 'message' => 'NIK, No. KK, atau Nama Lengkap tidak sesuai dengan data penduduk wilayah yang dipilih.');
    }

    private function identity_digits($value)
    {
        return preg_replace('/[^0-9]/', '', (string) $value);
    }

    private function identity_name($value)
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function profile_identity_hash($value)
    {
        return hash_hmac('sha256', (string) $value, (string) $this->config->item('encryption_key'));
    }

    /**
     * Encrypt a 16-digit identity value for the account owner's profile.
     * AES-256-GCM provides confidentiality and tamper detection; the binary
     * payload starts with a small version marker for future key migrations.
     */
    private function encrypt_identity($value)
    {
        if (!function_exists('openssl_encrypt') || !function_exists('random_bytes')) return NULL;
        $value = $this->identity_digits($value);
        if (!preg_match('/^[0-9]{16}$/', $value)) return NULL;
        $key = hash('sha256', 'smartdesa-warga:citizen-profile:v1|' . (string) $this->config->item('encryption_key'), TRUE);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '');
        if ($ciphertext === FALSE || strlen($tag) !== 16) return NULL;
        return 'v1' . $iv . $tag . $ciphertext;
    }

    private function decrypt_identity($value)
    {
        if (!function_exists('openssl_decrypt') || !is_string($value) || strlen($value) < 30) return '';
        if (substr($value, 0, 2) !== 'v1') return '';
        $iv = substr($value, 2, 12);
        $tag = substr($value, 14, 16);
        $ciphertext = substr($value, 30);
        if (strlen($iv) !== 12 || strlen($tag) !== 16 || $ciphertext === '') return '';
        $key = hash('sha256', 'smartdesa-warga:citizen-profile:v1|' . (string) $this->config->item('encryption_key'), TRUE);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '');
        return is_string($plain) && preg_match('/^[0-9]{16}$/', $plain) ? $plain : '';
    }

    public function request_password_reset($email)
    {
        $email = strtolower(trim((string) $email));
        if (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array('success' => FALSE, 'message' => 'Masukkan alamat email yang valid.');
        }
        return $this->password_reset_api_post('password-resets/request', array('email' => $email));
    }

    public function complete_password_reset($requestToken, $otp, $newPassword)
    {
        return $this->password_reset_api_post('password-resets/complete', array(
            'request_token' => strtolower(trim((string) $requestToken)),
            'otp' => preg_replace('/\D+/', '', (string) $otp),
            'new_password' => (string) $newPassword
        ));
    }

    public function request_account_change($userId, $currentPassword, $purpose, $email = '', $phone = '')
    {
        return $this->password_reset_api_post('account-security/request', array(
            'account_id' => (int) $userId,
            'current_password' => (string) $currentPassword,
            'purpose' => (string) $purpose,
            'target_email' => strtolower(trim((string) $email)),
            'target_phone' => trim((string) $phone)
        ));
    }

    public function complete_account_change($userId, $purpose, $requestToken, $otp, $email = '', $phone = '', $newPassword = '')
    {
        return $this->password_reset_api_post('account-security/complete', array(
            'account_id' => (int) $userId,
            'purpose' => (string) $purpose,
            'request_token' => (string) $requestToken,
            'otp' => preg_replace('/\D+/', '', (string) $otp),
            'target_email' => strtolower(trim((string) $email)),
            'target_phone' => trim((string) $phone),
            'new_password' => (string) $newPassword
        ));
    }

    private function password_reset_api_post($endpoint, array $payload)
    {
        $tenantCode = $this->configured_tenant_code();
        if ($tenantCode !== '' && !isset($payload['tenant_code'])) $payload['tenant_code'] = $tenantCode;
        $base = rtrim(trim((string) getenv('WARGA_CENTRAL_API_URL')), '/');
        $url = $base . '/' . trim((string) $endpoint, '/');
        $parts = parse_url($url);
        if ($base === '' || !is_array($parts) || empty($parts['host'])
            || (ENVIRONMENT === 'production' && strtolower((string) (isset($parts['scheme']) ? $parts['scheme'] : '')) !== 'https')) {
            return array('success' => FALSE, 'message' => 'Layanan keamanan akun belum dikonfigurasi.');
        }
        if (!function_exists('curl_init')) {
            return array('success' => FALSE, 'message' => 'Layanan keamanan akun belum tersedia pada server.');
        }
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) return array('success' => FALSE, 'message' => 'Data keamanan akun belum dapat diproses.');

        $timeout = max(5, min(30, (int) (getenv('WARGA_CENTRAL_API_TIMEOUT') ?: 15)));
        $handle = curl_init($url);
        curl_setopt_array($handle, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTP_VERSION => defined('CURL_HTTP_VERSION_1_1') ? CURL_HTTP_VERSION_1_1 : 0,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Cache-Control: no-store',
                'Origin: ' . rtrim((string) getenv('APP_URL'), '/')
            ),
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_SSL_VERIFYHOST => 2
        ));
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($response === FALSE || $error !== '') {
            return array('success' => FALSE, 'message' => 'Layanan keamanan akun sedang tidak dapat dihubungi. Periksa koneksi lalu coba lagi.');
        }
        $decoded = json_decode((string) $response, TRUE);
        if (!is_array($decoded)) return array('success' => FALSE, 'message' => 'Respons layanan keamanan akun tidak valid.');
        if ($status < 200 || $status >= 300 || empty($decoded['success'])) {
            return array(
                'success' => FALSE,
                'message' => isset($decoded['message']) ? (string) $decoded['message'] : 'Permintaan keamanan akun belum dapat diproses.',
                'status' => $status
            );
        }
        return $decoded;
    }

    public function logout()
    {
        // Detach the current browser endpoint before destroying the session.
        // Keep the browser PushSubscription alive so a later login can bind it
        // to the new account; never remove another account's endpoint.
        $userId = (int) $this->session->userdata('warga_user_id');
        $endpointHash = trim((string) $this->session->userdata('warga_push_endpoint_hash'));
        if ($userId > 0 && $endpointHash !== '' && warga_database_available()) {
            try {
                if ($this->db->table_exists('warga_push_subscriptions')
                    && preg_match('/^[a-f0-9]{64}$/i', $endpointHash)) {
                    $this->db->where(array('user_id' => $userId,
                        'endpoint_hash' => strtolower($endpointHash)))->delete('warga_push_subscriptions');
                }
            } catch (Throwable $e) {
                // Logout must still complete if the optional push table is
                // unavailable or the database is temporarily read-only.
            }
        }
        $this->session->unset_userdata('warga_push_endpoint_hash');
        $this->revoke_current_trusted_device();
        $this->session->unset_userdata(array('warga_logged_in', 'warga_user_id', 'warga_session_version', 'intended_url'));
        $this->session->sess_regenerate(TRUE);
    }
}
