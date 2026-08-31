<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model
{
    private $lastError = '';

    private function client_ip()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function identity_hash($identity)
    {
        $normalized = strtolower(trim((string) $identity));
        $normalized = preg_replace('/\s+/', '', $normalized);
        return hash_hmac('sha256', $normalized, (string) $this->config->item('encryption_key'));
    }

    private function is_login_throttled($identity)
    {
        if (warga_demo_mode() || !warga_database_available()) return FALSE;
        $since = date('Y-m-d H:i:s', time() - 900);
        $identityCount = $this->db->where('identity_hash', $this->identity_hash($identity))->where('attempted_at >=', $since)->count_all_results('login_failures');
        $ipCount = $this->db->where('ip_address', $this->client_ip())->where('attempted_at >=', $since)->count_all_results('login_failures');
        return $identityCount >= 8 || $ipCount >= 30;
    }

    private function record_login_failure($identity)
    {
        if (warga_demo_mode() || !warga_database_available()) return;
        if (mt_rand(1, 20) === 1) $this->db->where('attempted_at <', date('Y-m-d H:i:s', time() - 86400))->delete('login_failures');
        $this->db->insert('login_failures', array('identity_hash' => $this->identity_hash($identity), 'ip_address' => $this->client_ip()));
    }

    private function clear_login_failures($identity)
    {
        if (warga_demo_mode() || !warga_database_available()) return;
        $this->db->where('identity_hash', $this->identity_hash($identity))->delete('login_failures');
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
        return $this->db->select('u.id,u.role_id,u.name,u.username,u.email,u.phone,u.is_active,u.last_login_at,u.village_id,r.name AS role_name,r.slug AS role_slug,v.village_code,v.name AS village_name,v.district_name,v.regency_code,v.regency_name')
            ->from('users u')->join('roles r', 'r.id=u.role_id')->join('village_tenants v', 'v.id=u.village_id', 'left')
            ->where(array('u.id' => (int) $this->session->userdata('warga_user_id'), 'u.is_active' => 1))
            ->get()->row_array();
    }

    public function register_citizen(array $data)
    {
        if (warga_demo_mode()) return array('success' => TRUE);
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database belum tersedia.');

        $name = trim((string) $data['name']);
        $contact = trim((string) $data['contact']);
        $districtCode = strtoupper(trim((string) (isset($data['district_code']) ? $data['district_code'] : '')));
        $villageCode = strtoupper(trim((string) $data['village_code']));
        $email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? strtolower($contact) : NULL;
        $phone = $email === NULL ? preg_replace('/[^0-9+]/', '', $contact) : NULL;
        if ($email === NULL && strlen($phone) < 8) return array('success' => FALSE, 'message' => 'Email atau nomor telepon belum valid.');

        $village = $this->db->where('village_code', $villageCode)->where('status', 'active')->get('village_tenants')->row_array();
        if (!$village) return array('success' => FALSE, 'message' => 'Kampung/Desa yang dipilih belum terdaftar atau tidak aktif.');
        if ($districtCode !== '' && strtoupper(trim((string) $village['district_code'])) !== $districtCode) {
            return array('success' => FALSE, 'message' => 'Pilihan distrik dan kampung/desa tidak sesuai. Silakan pilih ulang.');
        }
        $role = $this->db->where('slug', 'warga')->get('roles')->row_array();
        if (!$role) return array('success' => FALSE, 'message' => 'Peran warga belum disiapkan pada server.');

        $this->db->group_start();
        if ($email !== NULL) $this->db->where('email', $email);
        if ($phone !== NULL) $this->db->or_where('phone', $phone);
        $this->db->group_end();
        if ($this->db->count_all_results('users') > 0) return array('success' => FALSE, 'message' => 'Email atau nomor telepon sudah terdaftar.');

        $username = 'warga_' . strtolower(substr(preg_replace('/[^a-z0-9]+/i', '', $name), 0, 20)) . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $inserted = $this->db->insert('users', array(
            'role_id' => (int) $role['id'],
            'village_id' => $village['id'],
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'is_active' => 1
        ));
        return $inserted ? array('success' => TRUE) : array('success' => FALSE, 'message' => 'Pendaftaran belum dapat disimpan.');
    }

    public function logout()
    {
        $this->session->unset_userdata(array('warga_logged_in', 'warga_user_id', 'intended_url'));
        $this->session->sess_regenerate(TRUE);
    }
}
