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
            'regency_name' => 'Jayawijaya'
        );
        return array(
            1 => array_merge($scope, array('id' => 1, 'role_id' => 1, 'role_slug' => 'warga', 'role_name' => 'Warga', 'name' => 'Yotam Wamena', 'username' => 'warga', 'email' => 'warga@demo.local', 'phone' => '081234567890')),
            2 => array_merge($scope, array('id' => 2, 'role_id' => 2, 'role_slug' => 'sekdes', 'role_name' => 'Sekretaris Desa', 'name' => 'Markus Huby', 'username' => 'sekdes', 'email' => 'sekdes@demo.local', 'phone' => '081234567891')),
            3 => array_merge($scope, array('id' => 3, 'role_id' => 3, 'role_slug' => 'kepala-desa', 'role_name' => 'Kepala Desa', 'name' => 'Yulius Wenda', 'username' => 'kades', 'email' => 'kades@demo.local', 'phone' => '081234567892'))
        );
    }

    private function demo_user($id)
    {
        $users = $this->demo_users();
        return isset($users[(int) $id]) ? $users[(int) $id] : NULL;
    }

    public function attempt($identity, $password)
    {
        $this->lastError = '';
        $identity = trim((string) $identity);
        if (warga_demo_mode()) {
            if (!hash_equals('demo12345', (string) $password)) return FALSE;
            $identity = strtolower($identity);
            foreach ($this->demo_users() as $user) {
                if (in_array($identity, array(strtolower($user['username']), strtolower($user['email']), strtolower($user['phone'])), TRUE)) {
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
        if (!$village) return array('success' => FALSE, 'message' => 'Kampung/Desa yang dipilih belum terdaftar atau tidak aktif.');
        if ($districtCode !== '' && strtoupper(trim((string) $village['district_code'])) !== $districtCode) {
            return array('success' => FALSE, 'message' => 'Pilihan distrik dan kampung/desa tidak sesuai. Silakan pilih ulang.');
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
        $this->db->trans_begin();
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
            'nik_hash' => $this->profile_identity_hash($nik),
            'kk_hash' => $this->profile_identity_hash($kk),
            'name_hash' => $this->profile_identity_hash($this->identity_name($canonicalName)),
            'birth_date' => isset($resident['birth_date']) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', (string) $resident['birth_date']) ? $resident['birth_date'] : NULL,
            'gender' => isset($resident['gender']) ? trim((string) $resident['gender']) : NULL,
            'verification_status' => 'verified',
            'created_at' => $now,
            'updated_at' => $now
        );
        if (!$this->db->insert('citizen_profiles', $profile) || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Profil penduduk belum dapat disimpan.');
        }
        $this->db->trans_commit();
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
        $query = $this->db->query('SHOW INDEX FROM `citizen_profiles`');
        $hasUniqueIndex = FALSE;
        if ($query) foreach ($query->result_array() as $row) if (isset($row['Key_name']) && $row['Key_name'] === 'uniq_citizen_source') $hasUniqueIndex = TRUE;
        if (!$hasUniqueIndex && $this->db->field_exists('village_id', 'citizen_profiles') && $this->db->field_exists('local_citizen_key', 'citizen_profiles')) {
            $this->db->query('ALTER TABLE `citizen_profiles` ADD UNIQUE KEY `uniq_citizen_source` (`village_id`, `local_citizen_key`)');
        }
        $this->identity_schema_ready = $this->db->field_exists('local_citizen_key', 'citizen_profiles')
            && $this->db->field_exists('name_hash', 'citizen_profiles')
            && $this->db->field_exists('verification_status', 'citizen_profiles');
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
            return array('success' => FALSE, 'message' => 'Data penduduk kampung belum tersinkron ke layanan warga. Silakan coba lagi setelah SmartDesa desa terhubung ke internet.');
        }
        if ($code === 'rate_limited') return array('success' => FALSE, 'message' => 'Terlalu banyak percobaan. Silakan tunggu beberapa menit lalu coba lagi.');
        return array('success' => FALSE, 'message' => 'NIK, No. KK, atau Nama Lengkap tidak sesuai dengan data penduduk kampung yang dipilih.');
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

    public function logout()
    {
        $this->session->unset_userdata(array('warga_logged_in', 'warga_user_id', 'intended_url'));
        $this->session->sess_regenerate(TRUE);
    }
}
