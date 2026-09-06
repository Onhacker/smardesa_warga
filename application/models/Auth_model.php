<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model
{
    private $lastError = '';
    private $identity_schema_ready = false;

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
        if (warga_demo_mode() || !warga_database_available()) return FALSE;
        $since = date('Y-m-d H:i:s', time() - 900);
        $identityCount = $this->db->where('identity_hash', $this->login_identity_hash($identity))->where('attempted_at >=', $since)->count_all_results('login_failures');
        $ipCount = $this->db->where('ip_address', $this->client_ip())->where('attempted_at >=', $since)->count_all_results('login_failures');
        return $identityCount >= 8 || $ipCount >= 30;
    }

    private function record_login_failure($identity)
    {
        if (warga_demo_mode() || !warga_database_available()) return;
        if (mt_rand(1, 20) === 1) $this->db->where('attempted_at <', date('Y-m-d H:i:s', time() - 86400))->delete('login_failures');
        $this->db->insert('login_failures', array('identity_hash' => $this->login_identity_hash($identity), 'ip_address' => $this->client_ip()));
    }

    private function clear_login_failures($identity)
    {
        if (warga_demo_mode() || !warga_database_available()) return;
        $this->db->where('identity_hash', $this->login_identity_hash($identity))->delete('login_failures');
    }

    public function error()
    {
        return $this->lastError;
    }

    /**
     * Daftar wilayah aktif untuk formulir pendaftaran warga.
     * Kode wilayah tetap berasal dari server; warga hanya memilih nama wilayah.
     */
    public function registration_regions()
    {
        if (warga_demo_mode()) {
            return array(array(
                'district_code' => '95.01.03',
                'district_name' => 'Asologaima',
                'village_code' => '95.01.03.2003',
                'village_name' => 'Kampung Araboda',
                'regency_name' => 'Jayawijaya',
                'province_name' => 'Papua Pegunungan'
            ));
        }

        if (!warga_database_available() || !$this->db->table_exists('village_tenants')) {
            return array();
        }

        return $this->db
            ->select('district_code, district_name, village_code, name AS village_name, regency_name, province_name')
            ->where('status', 'active')
            ->order_by('district_name', 'ASC')
            ->order_by('name', 'ASC')
            ->get('village_tenants')
            ->result_array();
    }

    private function demo_users()
    {
        $scope = array(
            'village_id' => '00000000-0000-4000-8000-000000000001',
            'village_code' => '95.01.03.2003',
            'village_name' => 'Kampung Araboda',
            'district_name' => 'Asologaima',
            'regency_code' => '95.01',
            'regency_name' => 'Jayawijaya',
            'institution' => 'Kampung'
        );
        return array(
            1 => array_merge($scope, array('id' => 1, 'role_id' => 1, 'role_slug' => 'warga', 'role_name' => 'Warga', 'name' => 'Yotam Wamena', 'username' => 'warga', 'email' => 'warga@demo.local', 'phone' => '081234567890')),
            2 => array_merge($scope, array('id' => 2, 'role_id' => 2, 'role_slug' => 'sekdes', 'role_name' => 'Sekretaris Kampung', 'name' => 'Markus Huby', 'username' => 'sekdes', 'email' => 'sekdes@demo.local', 'phone' => '081234567891')),
            3 => array_merge($scope, array('id' => 3, 'role_id' => 3, 'role_slug' => 'kepala-desa', 'role_name' => 'Kepala Kampung', 'name' => 'Yulius Wenda', 'username' => 'kades', 'email' => 'kades@demo.local', 'phone' => '081234567892'))
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
            $identity = strtolower($identity);
            foreach ($this->demo_users() as $user) {
                $effective = $this->demo_user((int) $user['id']);
                $overrides = $this->session->userdata('warga_demo_overrides');
                $override = is_array($overrides) && isset($overrides[(string) (int) $user['id']]) && is_array($overrides[(string) (int) $user['id']]) ? $overrides[(string) (int) $user['id']] : array();
                $demoPasswordHash = $this->demo_password_hash($override);
                if (password_verify((string) $password, $demoPasswordHash) && in_array($identity, array(strtolower($effective['username']), strtolower($effective['email']), strtolower($effective['phone'])), TRUE)) {
                    $this->session->sess_regenerate(TRUE);
                    $this->session->set_userdata(array('warga_logged_in' => TRUE, 'warga_user_id' => (int) $user['id']));
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
        if (!$user || !password_verify((string) $password, $user['password_hash'])) {
            $this->record_login_failure($identity);
            return FALSE;
        }

        $this->clear_login_failures($identity);
        $this->session->sess_regenerate(TRUE);
        $this->session->set_userdata(array('warga_logged_in' => TRUE, 'warga_user_id' => (int) $user['id']));
        $this->db->where('id', $user['id'])->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
        return $user;
    }

    public function current_user()
    {
        if (!$this->session->userdata('warga_logged_in') || !$this->session->userdata('warga_user_id')) return NULL;
        if (warga_demo_mode()) return $this->demo_user((int) $this->session->userdata('warga_user_id'));
        if (!warga_database_available()) return NULL;
        $identityReady = $this->ensure_identity_schema();
        $select = 'u.id,u.role_id,u.name,u.username,u.email,u.phone,u.is_active,u.last_login_at,u.village_id,r.name AS role_name,r.slug AS role_slug,v.village_code,v.name AS village_name,v.district_name,v.regency_code,v.regency_name';
        if ($identityReady) {
            $select .= ',cp.verification_status AS citizen_verification_status,cp.local_citizen_key';
        } else {
            $select .= ",'unverified' AS citizen_verification_status,NULL AS local_citizen_key";
        }
        $this->db->select($select, FALSE)->from('users u')
            ->join('roles r', 'r.id=u.role_id')
            ->join('village_tenants v', 'v.id=u.village_id', 'left');
        if ($identityReady) $this->db->join('citizen_profiles cp', 'cp.user_id=u.id', 'left');
        return $this->db
            ->where(array('u.id' => (int) $this->session->userdata('warga_user_id'), 'u.is_active' => 1))
            ->get()->row_array();
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
            return array('success' => TRUE);
        }
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database belum tersedia.');
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') return array('success' => FALSE, 'message' => 'Kata sandi belum dapat diubah.');
        $updated = $this->db->where('id', $userId)->update('users', array('password_hash' => $hash, 'updated_at' => date('Y-m-d H:i:s')));
        if ($updated) $this->session->sess_regenerate(TRUE);
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
            $empty['address'] = 'Kampung Araboda, Distrik Asologaima';
            $empty['verification_status'] = 'verified';
            $empty['identity_stored'] = TRUE;
            $empty['identity_note'] = 'Data identitas pada mode demo disamarkan.';
            return $empty;
        }
        if (!warga_database_available() || !$this->ensure_identity_schema()) {
            $empty['identity_note'] = 'Profil kependudukan belum tersedia pada server.';
            return $empty;
        }

        $profileFields = array();
        foreach (array('birth_date', 'gender', 'address_snapshot', 'verification_status', 'nik_encrypted', 'kk_encrypted') as $field) {
            if ($this->db->field_exists($field, 'citizen_profiles')) $profileFields[] = 'cp.' . $field;
        }
        if (!$profileFields) {
            $empty['identity_note'] = 'Profil kependudukan belum tersedia pada server.';
            return $empty;
        }

        $this->db->select(implode(',', $profileFields), FALSE)->from('citizen_profiles cp');
        $hasDirectory = $this->db->table_exists('village_resident_directory')
            && $this->db->field_exists('local_citizen_key', 'citizen_profiles');
        if ($hasDirectory && $this->db->field_exists('birth_date', 'village_resident_directory')
            && $this->db->field_exists('gender', 'village_resident_directory')) {
            $this->db->select('d.birth_date AS directory_birth_date,d.gender AS directory_gender', FALSE)
                ->join('village_resident_directory d', 'd.village_id=cp.village_id AND d.local_citizen_key=cp.local_citizen_key', 'left');
        }
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
        $contact = trim((string) $data['contact']);
        $nik = $this->identity_digits(isset($data['nik']) ? $data['nik'] : '');
        $kk = $this->identity_digits(isset($data['kk']) ? $data['kk'] : '');
        $districtCode = strtoupper(trim((string) (isset($data['district_code']) ? $data['district_code'] : '')));
        $villageCode = strtoupper(trim((string) $data['village_code']));
        $email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? strtolower($contact) : NULL;
        $phone = $email === NULL ? preg_replace('/[^0-9+]/', '', $contact) : NULL;
        if (!preg_match('/^[0-9]{16}$/', $nik) || !preg_match('/^[0-9]{16}$/', $kk)) {
            return array('success' => FALSE, 'message' => 'NIK dan No. KK harus terdiri dari 16 digit angka.');
        }
        if ($email === NULL && strlen($phone) < 8) return array('success' => FALSE, 'message' => 'Email atau nomor telepon belum valid.');

        $village = $this->db->where('village_code', $villageCode)->where('status', 'active')->get('village_tenants')->row_array();
        if (!$village) return array('success' => FALSE, 'message' => 'Wilayah yang dipilih belum terdaftar atau tidak aktif.');
        if ($districtCode !== '' && strtoupper(trim((string) $village['district_code'])) !== $districtCode) {
            return array('success' => FALSE, 'message' => 'Pilihan distrik dan wilayah tidak sesuai. Silakan pilih ulang.');
        }
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
        if ($email !== NULL) $this->db->where('email', $email);
        if ($phone !== NULL) $this->db->or_where('phone', $phone);
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
            'phone' => $phone,
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
        // plaintext identity values in the database.  Older installations
        // may not have the optional columns yet; ensure_identity_schema()
        // adds them lazily and the conditional checks keep registration
        // compatible with a read-only/legacy schema.
        $encryptedNik = $this->encrypt_identity($nik);
        $encryptedKk = $this->encrypt_identity($kk);
        if ($encryptedNik !== NULL && $this->db->field_exists('nik_encrypted', 'citizen_profiles')) {
            $profile['nik_encrypted'] = $encryptedNik;
        }
        if ($encryptedKk !== NULL && $this->db->field_exists('kk_encrypted', 'citizen_profiles')) {
            $profile['kk_encrypted'] = $encryptedKk;
        }
        if (!$this->db->insert('citizen_profiles', $profile) || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Profil penduduk belum dapat disimpan.');
        }
        if (!$this->db->trans_commit()) {
            return array('success' => FALSE, 'message' => 'Pendaftaran belum dapat diselesaikan. Silakan coba lagi.');
        }
        return array('success' => TRUE);
    }

    public function citizen_is_verified($userId, $villageId = '')
    {
        if (warga_demo_mode()) return TRUE;
        if (!warga_database_available() || !$this->ensure_identity_schema()
            || !$this->db->table_exists('village_resident_directory')) return FALSE;
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
        if ($this->identity_schema_ready) return TRUE;
        if (!warga_database_available() || !$this->db->table_exists('citizen_profiles')) return FALSE;
        if (!$this->db->field_exists('local_citizen_key', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `local_citizen_key` VARCHAR(120) DEFAULT NULL");
        }
        if (!$this->db->field_exists('name_hash', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `name_hash` CHAR(64) DEFAULT NULL");
        }
        if (!$this->db->field_exists('verification_status', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `verification_status` VARCHAR(30) NOT NULL DEFAULT 'unverified'");
        }
        if (!$this->db->field_exists('nik_encrypted', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `nik_encrypted` VARBINARY(512) DEFAULT NULL");
        }
        if (!$this->db->field_exists('kk_encrypted', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `kk_encrypted` VARBINARY(512) DEFAULT NULL AFTER `nik_encrypted`");
        }
        if (!$this->db->field_exists('birth_date', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `birth_date` DATE DEFAULT NULL");
        }
        if (!$this->db->field_exists('gender', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `gender` VARCHAR(20) DEFAULT NULL");
        }
        if (!$this->db->field_exists('address_snapshot', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `address_snapshot` TEXT DEFAULT NULL");
        }
        if (!$this->db->field_exists('nik_hash', 'citizen_profiles')) {
            $this->db->query("ALTER TABLE `citizen_profiles` ADD `nik_hash` CHAR(64) DEFAULT NULL");
        }
        $query = $this->db->query('SHOW INDEX FROM `citizen_profiles`');
        $hasUniqueIndex = FALSE;
        $hasGlobalNikIndex = FALSE;
        if ($query) foreach ($query->result_array() as $row) {
            if (isset($row['Key_name']) && $row['Key_name'] === 'uniq_citizen_source') $hasUniqueIndex = TRUE;
            if (isset($row['Key_name']) && $row['Key_name'] === 'uniq_citizen_nik_global') $hasGlobalNikIndex = TRUE;
        }
        if (!$hasUniqueIndex && $this->db->field_exists('village_id', 'citizen_profiles') && $this->db->field_exists('local_citizen_key', 'citizen_profiles')) {
            $this->db->query('ALTER TABLE `citizen_profiles` ADD UNIQUE KEY `uniq_citizen_source` (`village_id`, `local_citizen_key`)');
        }
        if (!$hasGlobalNikIndex && $this->db->field_exists('nik_hash', 'citizen_profiles')) {
            $previousDebug = $this->db->db_debug;
            $this->db->db_debug = FALSE;
            $this->db->query('ALTER TABLE `citizen_profiles` ADD UNIQUE KEY `uniq_citizen_nik_global` (`nik_hash`)');
            $this->db->db_debug = $previousDebug;
        }
        $indexQuery = $this->db->query('SHOW INDEX FROM `citizen_profiles`');
        $hasUniqueIndex = FALSE;
        $hasGlobalNikIndex = FALSE;
        if ($indexQuery) foreach ($indexQuery->result_array() as $row) {
            if (isset($row['Key_name']) && $row['Key_name'] === 'uniq_citizen_source') $hasUniqueIndex = TRUE;
            if (isset($row['Key_name']) && $row['Key_name'] === 'uniq_citizen_nik_global') $hasGlobalNikIndex = TRUE;
        }
        $this->identity_schema_ready = $this->db->field_exists('local_citizen_key', 'citizen_profiles')
            && $this->db->field_exists('name_hash', 'citizen_profiles')
            && $this->db->field_exists('verification_status', 'citizen_profiles')
            && $hasUniqueIndex
            && $hasGlobalNikIndex;
        return $this->identity_schema_ready;
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
            return array('success' => FALSE, 'message' => 'Data penduduk wilayah belum tersinkron ke layanan warga. Silakan coba lagi setelah SmartDesa terhubung ke internet.');
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

    public function logout()
    {
        $this->session->unset_userdata(array('warga_logged_in', 'warga_user_id', 'intended_url'));
        $this->session->sess_regenerate(TRUE);
    }
}
