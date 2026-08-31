<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Request_model extends CI_Model
{
    private function normalize_row(array $row)
    {
        if (isset($row['payload_json'])) {
            $payload = json_decode((string) $row['payload_json'], TRUE);
            if (is_array($payload)) {
                $row['purpose'] = isset($payload['purpose']) ? $payload['purpose'] : '';
                $row['note'] = isset($payload['note']) ? $payload['note'] : '';
            }
        }
        if (!isset($row['purpose'])) $row['purpose'] = '';
        if (!isset($row['note'])) $row['note'] = '';
        return $row;
    }

    private function private_storage_path()
    {
        $configured = trim((string) getenv('PRIVATE_STORAGE_PATH'));
        if (ENVIRONMENT === 'production' && $configured === '') return NULL;
        $path = $configured !== '' ? $configured : FCPATH . 'storage';
        if (!is_dir($path) && !@mkdir($path, 0750, TRUE) && !is_dir($path)) return NULL;
        $real = realpath($path);
        if ($real === FALSE || !is_dir($real) || !is_readable($real) || !is_writable($real)) return NULL;
        if (ENVIRONMENT === 'production') {
            $public = realpath(FCPATH);
            if ($public !== FALSE && strpos(rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, rtrim($public, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) return NULL;
        }
        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function collect_uploaded_documents($requestId)
    {
        if (empty($_FILES['supporting_files']) || !is_array($_FILES['supporting_files']['name'])) return array('files' => array(), 'error' => NULL);
        $storage = $this->private_storage_path();
        if ($storage === NULL) return array('files' => array(), 'error' => 'Penyimpanan berkas belum siap.');
        $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf');
        $files = array();
        $paths = array();
        $count = count($_FILES['supporting_files']['name']);
        if ($count > 5) return array('files' => array(), 'error' => 'Maksimal lima berkas dapat dikirim.');
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : NULL;
        for ($index = 0; $index < $count; $index++) {
            $errorCode = (int) $_FILES['supporting_files']['error'][$index];
            if ($errorCode === UPLOAD_ERR_NO_FILE) continue;
            if ($errorCode !== UPLOAD_ERR_OK) {
                if ($finfo) finfo_close($finfo);
                $this->cleanup_paths($paths);
                return array('files' => array(), 'error' => 'Salah satu berkas gagal diunggah.');
            }
            $tmp = $_FILES['supporting_files']['tmp_name'][$index];
            $size = (int) $_FILES['supporting_files']['size'][$index];
            if ($size < 1 || $size > 5 * 1024 * 1024 || !is_uploaded_file($tmp)) {
                if ($finfo) finfo_close($finfo);
                $this->cleanup_paths($paths);
                return array('files' => array(), 'error' => 'Setiap berkas harus berukuran maksimal 5 MB.');
            }
            $mime = $finfo ? finfo_file($finfo, $tmp) : (string) $_FILES['supporting_files']['type'][$index];
            if (!isset($allowed[$mime])) {
                if ($finfo) finfo_close($finfo);
                $this->cleanup_paths($paths);
                return array('files' => array(), 'error' => 'Jenis berkas hanya boleh JPG, PNG, atau PDF.');
            }
            $name = $requestId . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            $destination = $storage . DIRECTORY_SEPARATOR . 'requests' . DIRECTORY_SEPARATOR . $name;
            $directory = dirname($destination);
            if (!is_dir($directory) && !@mkdir($directory, 0750, TRUE) && !is_dir($directory)) {
                if ($finfo) finfo_close($finfo);
                $this->cleanup_paths($paths);
                return array('files' => array(), 'error' => 'Folder berkas belum dapat dibuat.');
            }
            if (!move_uploaded_file($tmp, $destination)) {
                if ($finfo) finfo_close($finfo);
                $this->cleanup_paths($paths);
                return array('files' => array(), 'error' => 'Berkas belum dapat disimpan.');
            }
            @chmod($destination, 0640);
            $paths[] = $destination;
            $files[] = array('original_name' => substr((string) $_FILES['supporting_files']['name'][$index], 0, 180), 'stored_name' => $name, 'storage_path' => $destination, 'mime_type' => $mime, 'file_size' => $size);
        }
        if ($finfo) finfo_close($finfo);
        return array('files' => $files, 'error' => NULL, 'paths' => $paths);
    }

    private function cleanup_paths(array $paths)
    {
        foreach ($paths as $path) if (is_string($path) && is_file($path)) @unlink($path);
    }

    private function demo_services()
    {
        return array(
            array('id' => 1, 'slug' => 'domisili', 'name' => 'Surat Keterangan Domisili', 'short_name' => 'Domisili', 'icon' => 'fa-home', 'description' => 'Keterangan tempat tinggal warga.', 'requirements' => array('Kartu Keluarga', 'Kartu Tanda Penduduk')),
            array('id' => 2, 'slug' => 'tidak-mampu', 'name' => 'Surat Keterangan Tidak Mampu', 'short_name' => 'Tidak Mampu', 'icon' => 'fa-hands-helping', 'description' => 'Keterangan kondisi sosial ekonomi warga.', 'requirements' => array('Kartu Keluarga', 'Kartu Tanda Penduduk')),
            array('id' => 3, 'slug' => 'usaha', 'name' => 'Surat Keterangan Usaha', 'short_name' => 'Keterangan Usaha', 'icon' => 'fa-store', 'description' => 'Keterangan kegiatan usaha warga.', 'requirements' => array('Kartu Keluarga', 'Kartu Tanda Penduduk', 'Keterangan lokasi usaha'))
        );
    }

    private function demo_defaults()
    {
        return array(
            array('id' => 'demo-request-000000000000000000000000000003', 'request_code' => 'SDW-2026-0003', 'citizen_user_id' => 99, 'service_slug' => 'usaha', 'service_name' => 'Surat Keterangan Usaha', 'service_icon' => 'fa-store', 'status' => 'submitted', 'submitted_at' => '2026-08-31 16:20:00', 'updated_at' => '2026-08-31 16:20:00', 'purpose' => 'Persyaratan pengajuan bantuan usaha', 'note' => 'Usaha kios sembako berada di Kampung Araboda.', 'local_reference' => NULL, 'document_path' => NULL, 'citizen_name' => 'Mabel Wenda', 'citizen_phone' => '081234567893', 'village_name' => 'Kampung Araboda'),
            array('id' => 'demo-request-000000000000000000000000000001', 'request_code' => 'SDW-2026-0001', 'citizen_user_id' => 1, 'service_slug' => 'domisili', 'service_name' => 'Surat Keterangan Domisili', 'service_icon' => 'fa-home', 'status' => 'issued', 'submitted_at' => '2026-08-26 09:14:00', 'updated_at' => '2026-08-27 15:40:00', 'purpose' => 'Keperluan administrasi sekolah', 'note' => '', 'local_reference' => 'SKD/01/DEMO/VIII/2026', 'document_path' => NULL, 'citizen_name' => 'Yotam Wamena', 'citizen_phone' => '081234567890', 'village_name' => 'Kampung Araboda'),
            array('id' => 'demo-request-000000000000000000000000000002', 'request_code' => 'SDW-2026-0002', 'citizen_user_id' => 1, 'service_slug' => 'tidak-mampu', 'service_name' => 'Surat Keterangan Tidak Mampu', 'service_icon' => 'fa-hands-helping', 'status' => 'verified', 'submitted_at' => '2026-08-30 10:22:00', 'updated_at' => '2026-08-30 13:05:00', 'purpose' => 'Pengajuan bantuan pendidikan', 'note' => 'Mohon diproses sesuai jadwal pelayanan.', 'local_reference' => NULL, 'document_path' => NULL, 'citizen_name' => 'Yotam Wamena', 'citizen_phone' => '081234567890', 'village_name' => 'Kampung Araboda')
        );
    }

    private function demo_requests()
    {
        $saved = $this->session->userdata('warga_demo_requests');
        if (!is_array($saved)) $saved = array();
        $rows = array_merge($saved, $this->demo_defaults());
        $overrides = $this->session->userdata('warga_demo_request_overrides');
        if (!is_array($overrides)) $overrides = array();
        foreach ($rows as &$row) {
            if (isset($overrides[$row['id']]) && is_array($overrides[$row['id']])) $row = array_merge($row, $overrides[$row['id']]);
        }
        unset($row);
        usort($rows, function ($left, $right) { return strcmp((string) $right['submitted_at'], (string) $left['submitted_at']); });
        return $rows;
    }

    private function find_demo_request($id)
    {
        foreach ($this->demo_requests() as $row) if ((string) $row['id'] === (string) $id) return $row;
        return NULL;
    }

    private function apply_staff_scope(array $user)
    {
        $role = isset($user['role_slug']) ? $user['role_slug'] : '';
        if ($role === 'admin-pusat') return;
        if ($role === 'admin-kabupaten' && !empty($user['regency_code'])) {
            $this->db->where('v.regency_code', $user['regency_code']);
            return;
        }
        if (!empty($user['village_id'])) $this->db->where('sr.village_id', $user['village_id']);
        else $this->db->where('sr.id', '');
    }

    private function demo_staff_request_rows()
    {
        $rows = $this->demo_requests();
        foreach ($rows as &$row) {
            if (empty($row['citizen_name'])) $row['citizen_name'] = 'Yotam Wamena';
            if (empty($row['citizen_phone'])) $row['citizen_phone'] = '081234567890';
            if (empty($row['village_name'])) $row['village_name'] = 'Kampung Araboda';
        }
        unset($row);
        return $rows;
    }

    public function service_types()
    {
        if (warga_demo_mode() || !warga_database_available()) return $this->demo_services();
        $rows = $this->db->where('is_active', 1)->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get('service_types')->result_array();
        foreach ($rows as &$row) {
            $row['requirements'] = json_decode((string) $row['requirements_json'], TRUE);
            if (!is_array($row['requirements'])) $row['requirements'] = array();
            $row['icon'] = $row['icon'] ?: 'fa-file-alt';
        }
        unset($row);
        return $rows;
    }

    public function for_user($userId)
    {
        if (warga_demo_mode() || !warga_database_available()) return array_values(array_filter($this->demo_requests(), function ($row) use ($userId) { return isset($row['citizen_user_id']) && (int) $row['citizen_user_id'] === (int) $userId; }));
        $rows = $this->db->select('sr.*, st.slug AS service_slug, st.name AS service_name, st.icon AS service_icon, v.name AS village_name')
            ->from('service_requests sr')->join('service_types st', 'st.id=sr.service_type_id')->join('village_tenants v', 'v.id=sr.village_id', 'left')
            ->where('sr.citizen_user_id', (int) $userId)->order_by('sr.submitted_at', 'DESC')->get()->result_array();
        foreach ($rows as &$row) $row = $this->normalize_row($row);
        unset($row);
        return $rows;
    }

    public function summary($userId)
    {
        $rows = $this->for_user($userId);
        $summary = array('total' => count($rows), 'active' => 0, 'issued' => 0, 'revision' => 0);
        foreach ($rows as $row) {
            if (in_array($row['status'], array('submitted', 'verified', 'approved', 'syncing'), TRUE)) $summary['active']++;
            if ($row['status'] === 'issued') $summary['issued']++;
            if ($row['status'] === 'revision') $summary['revision']++;
        }
        return $summary;
    }

    public function find_for_user($id, $userId)
    {
        if (warga_demo_mode() || !warga_database_available()) {
            foreach ($this->demo_requests() as $row) if ((string) $row['id'] === (string) $id && isset($row['citizen_user_id']) && (int) $row['citizen_user_id'] === (int) $userId) return $row;
            return NULL;
        }
        $row = $this->db->select('sr.*, st.slug AS service_slug, st.name AS service_name, st.icon AS service_icon, v.name AS village_name')
            ->from('service_requests sr')->join('service_types st', 'st.id=sr.service_type_id')->join('village_tenants v', 'v.id=sr.village_id', 'left')
            ->where(array('sr.id' => (string) $id, 'sr.citizen_user_id' => (int) $userId))->get()->row_array();
        return $row ? $this->normalize_row($row) : NULL;
    }

    public function for_staff(array $user, $status = NULL)
    {
        if (warga_demo_mode() || !warga_database_available()) {
            $rows = $this->demo_staff_request_rows();
            if ($status !== NULL && $status !== '') $rows = array_values(array_filter($rows, function ($row) use ($status) { return $row['status'] === $status; }));
            return $rows;
        }
        $this->db->select('sr.*, st.slug AS service_slug, st.name AS service_name, st.icon AS service_icon, u.name AS citizen_name, u.phone AS citizen_phone, u.email AS citizen_email, v.name AS village_name, v.regency_code, v.regency_name');
        $this->db->from('service_requests sr');
        $this->db->join('service_types st', 'st.id=sr.service_type_id');
        $this->db->join('users u', 'u.id=sr.citizen_user_id');
        $this->db->join('village_tenants v', 'v.id=sr.village_id');
        $this->apply_staff_scope($user);
        if ($status !== NULL && $status !== '') $this->db->where('sr.status', $status);
        $rows = $this->db->order_by('sr.submitted_at', 'DESC')->get()->result_array();
        foreach ($rows as &$row) $row = $this->normalize_row($row);
        unset($row);
        return $rows;
    }

    public function staff_summary(array $user)
    {
        $rows = $this->for_staff($user);
        $summary = array('total' => count($rows), 'verification' => 0, 'approval' => 0, 'issued' => 0, 'revision' => 0, 'rejected' => 0);
        foreach ($rows as $row) {
            if ($row['status'] === 'submitted') $summary['verification']++;
            if ($row['status'] === 'verified') $summary['approval']++;
            if ($row['status'] === 'issued') $summary['issued']++;
            if ($row['status'] === 'revision') $summary['revision']++;
            if ($row['status'] === 'rejected') $summary['rejected']++;
        }
        return $summary;
    }

    public function find_for_staff($id, array $user)
    {
        if (warga_demo_mode() || !warga_database_available()) return $this->find_demo_request($id);
        $this->db->select('sr.*, st.slug AS service_slug, st.name AS service_name, st.icon AS service_icon, u.name AS citizen_name, u.phone AS citizen_phone, u.email AS citizen_email, v.name AS village_name, v.regency_code, v.regency_name');
        $this->db->from('service_requests sr');
        $this->db->join('service_types st', 'st.id=sr.service_type_id');
        $this->db->join('users u', 'u.id=sr.citizen_user_id');
        $this->db->join('village_tenants v', 'v.id=sr.village_id');
        $this->db->where('sr.id', (string) $id);
        $this->apply_staff_scope($user);
        $row = $this->db->get()->row_array();
        return $row ? $this->normalize_row($row) : NULL;
    }

    public function allowed_actions(array $user, array $request)
    {
        $role = isset($user['role_slug']) ? $user['role_slug'] : '';
        $status = isset($request['status']) ? $request['status'] : '';
        if ($role === 'sekdes' && $status === 'submitted') return array('verify', 'revision', 'reject');
        if ($role === 'kepala-desa' && $status === 'verified') return array('approve', 'revision', 'reject');
        if ($role === 'admin-desa') {
            if ($status === 'submitted') return array('verify', 'revision', 'reject');
            if ($status === 'verified') return array('approve', 'revision', 'reject');
        }
        return array();
    }

    public function apply_action($id, array $user, $action, $note = '')
    {
        $request = $this->find_for_staff($id, $user);
        if (!$request) return array('success' => FALSE, 'message' => 'Permohonan tidak ditemukan atau bukan wilayah kerja Anda.');
        $actions = array(
            'verify' => array('status' => 'verified', 'label' => 'Diverifikasi Sekdes', 'default_note' => 'Berkas dan data awal telah diperiksa.'),
            'approve' => array('status' => 'approved', 'label' => 'Disetujui Kepala Desa', 'default_note' => 'Permohonan disetujui untuk diproses.'),
            'revision' => array('status' => 'revision', 'label' => 'Perlu perbaikan', 'default_note' => 'Permohonan memerlukan perbaikan data atau berkas.'),
            'reject' => array('status' => 'rejected', 'label' => 'Permohonan ditolak', 'default_note' => 'Permohonan belum dapat disetujui desa.')
        );
        if (!isset($actions[$action]) || !in_array($action, $this->allowed_actions($user, $request), TRUE)) return array('success' => FALSE, 'message' => 'Tindakan tidak tersedia untuk peran dan status ini.');
        $next = $actions[$action];
        if (in_array($action, array('revision', 'reject'), TRUE) && trim((string) $note) === '') return array('success' => FALSE, 'message' => 'Alasan wajib diisi untuk perbaikan atau penolakan.');
        $note = trim((string) $note) !== '' ? trim((string) $note) : $next['default_note'];
        $now = date('Y-m-d H:i:s');
        if (warga_demo_mode() || !warga_database_available()) {
            $historyMap = $this->session->userdata('warga_demo_history');
            if (!is_array($historyMap)) $historyMap = array();
            $history = isset($historyMap[(string) $id]) && is_array($historyMap[(string) $id]) ? $historyMap[(string) $id] : $this->history($id);
            $overrides = $this->session->userdata('warga_demo_request_overrides');
            if (!is_array($overrides)) $overrides = array();
            $overrides[(string) $id] = array('status' => $next['status'], 'updated_at' => $now);
            if ($next['status'] === 'issued') $overrides[(string) $id]['local_reference'] = 'DEMO/' . date('YmdHis');
            $this->session->set_userdata('warga_demo_request_overrides', $overrides);
            $history[] = array('status' => $next['status'], 'label' => $next['label'], 'note' => $note, 'occurred_at' => $now, 'actor_name' => $user['name']);
            $historyMap[(string) $id] = $history;
            $this->session->set_userdata('warga_demo_history', $historyMap);
            return array('success' => TRUE, 'status' => $next['status']);
        }

        $payload = json_encode(array('request_id' => $request['id'], 'request_code' => $request['request_code'], 'status' => $next['status'], 'note' => $note, 'actor_name' => $user['name']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->trans_begin();
        $this->db->where(array('id' => (string) $id, 'status' => $request['status']))->update('service_requests', array('status' => $next['status']));
        $updated = $this->db->affected_rows();
        if ($updated !== 1) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Status berubah karena diproses pengguna lain. Muat ulang halaman.');
        }
        $this->db->insert('request_status_history', array('request_id' => (string) $id, 'from_status' => $request['status'], 'to_status' => $next['status'], 'note' => $note, 'actor_id' => (int) $user['id']));
        $this->db->insert('notifications', array('id' => warga_uuid(), 'user_id' => (int) $request['citizen_user_id'], 'request_id' => (string) $id, 'title' => $request['service_name'], 'message' => $next['label'] . '. ' . $note));
        $this->db->insert('sync_messages', array('id' => warga_uuid(), 'village_id' => $request['village_id'], 'aggregate_type' => 'service_request', 'aggregate_id' => (string) $id, 'direction' => 'cloud_to_local', 'operation' => 'status_update', 'payload_json' => $payload, 'status' => 'pending', 'idempotency_key' => 'request-status:' . $id . ':' . $next['status'] . ':' . bin2hex(random_bytes(6))));
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Tindakan belum dapat disimpan.');
        }
        $this->db->trans_commit();
        return array('success' => TRUE, 'status' => $next['status']);
    }

    public function documents_for_staff($requestId, array $user)
    {
        if (warga_demo_mode() || !warga_database_available()) {
            $request = $this->find_demo_request($requestId);
            return !empty($request['documents']) ? $request['documents'] : array();
        }
        $request = $this->find_for_staff($requestId, $user);
        if (!$request) return array();
        return $this->db->select('d.id,d.original_name,d.mime_type,d.file_size')->from('request_documents d')->where('d.request_id', (string) $requestId)->order_by('d.created_at', 'ASC')->get()->result_array();
    }

    public function document_for_staff($documentId, array $user)
    {
        if (warga_demo_mode() || !warga_database_available()) return NULL;
        $this->db->select('d.*, r.id AS request_id')->from('request_documents d')->join('service_requests r', 'r.id=d.request_id')->where('d.id', (string) $documentId);
        $request = $this->db->get()->row_array();
        if (!$request || !$this->find_for_staff($request['request_id'], $user)) return NULL;
        return $request;
    }

    public function history($requestId)
    {
        if (warga_demo_mode() || !warga_database_available()) {
            $historyMap = $this->session->userdata('warga_demo_history');
            if (is_array($historyMap) && isset($historyMap[(string) $requestId]) && is_array($historyMap[(string) $requestId])) return $historyMap[(string) $requestId];
            $request = $this->find_demo_request($requestId);
            if (!$request) return array();
            $history = array(array('status' => 'submitted', 'label' => 'Permohonan diajukan', 'note' => 'Data berhasil diterima oleh sistem.', 'occurred_at' => $request['submitted_at']));
            $steps = array(
                'verified' => array('label' => 'Diverifikasi Sekdes', 'note' => 'Berkas dan data awal telah diperiksa.'),
                'approved' => array('label' => 'Disetujui Kepala Desa', 'note' => 'Permohonan disetujui untuk diproses.'),
                'issued' => array('label' => 'Surat diterbitkan', 'note' => 'Dokumen resmi telah diterbitkan desa.'),
                'revision' => array('label' => 'Perlu perbaikan', 'note' => 'Permohonan memerlukan perbaikan data atau berkas.'),
                'rejected' => array('label' => 'Permohonan ditolak', 'note' => 'Permohonan belum dapat disetujui desa.')
            );
            $statusOrder = array('verified', 'approved', 'issued');
            foreach ($statusOrder as $status) {
                if ($request['status'] === $status || ($status === 'verified' && in_array($request['status'], array('approved', 'issued'), TRUE)) || ($status === 'approved' && $request['status'] === 'issued')) {
                    $history[] = array('status' => $status, 'label' => $steps[$status]['label'], 'note' => $steps[$status]['note'], 'occurred_at' => $request['updated_at']);
                }
            }
            if (in_array($request['status'], array('revision', 'rejected'), TRUE)) $history[] = array('status' => $request['status'], 'label' => $steps[$request['status']]['label'], 'note' => $steps[$request['status']]['note'], 'occurred_at' => $request['updated_at']);
            return $history;
        }
        $rows = $this->db->select('h.*, h.to_status AS status, u.name AS actor_name')->from('request_status_history h')->join('users u', 'u.id=h.actor_id', 'left')
            ->where('h.request_id', (string) $requestId)->order_by('h.occurred_at', 'ASC')->get()->result_array();
        foreach ($rows as &$row) $row['label'] = warga_status_text($row['status']);
        unset($row);
        return $rows;
    }

    public function create(array $user, array $data)
    {
        $serviceSlug = trim((string) $data['service_type']);
        $purpose = trim((string) $data['purpose']);
        $note = trim((string) $data['note']);
        if (warga_demo_mode() || !warga_database_available()) {
            $service = NULL;
            foreach ($this->demo_services() as $candidate) if ($candidate['slug'] === $serviceSlug) $service = $candidate;
            if (!$service) return array('success' => FALSE, 'message' => 'Jenis layanan tidak ditemukan.');
            $id = warga_uuid();
            $created = array('id' => $id, 'request_code' => 'SDW-' . date('Y') . '-' . strtoupper(substr(str_replace('-', '', $id), 0, 6)), 'citizen_user_id' => (int) $user['id'], 'service_slug' => $service['slug'], 'service_name' => $service['name'], 'service_icon' => $service['icon'], 'status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'purpose' => $purpose, 'note' => $note, 'local_reference' => NULL, 'document_path' => NULL, 'citizen_name' => $user['name'], 'citizen_phone' => $user['phone'], 'village_name' => $user['village_name']);
            $uploaded = $this->collect_uploaded_documents($id);
            if (!empty($uploaded['error'])) return array('success' => FALSE, 'message' => $uploaded['error']);
            $created['documents'] = array_map(function ($file) { return array('original_name' => $file['original_name'], 'mime_type' => $file['mime_type'], 'file_size' => $file['file_size']); }, $uploaded['files']);
            $saved = $this->session->userdata('warga_demo_requests');
            if (!is_array($saved)) $saved = array();
            array_unshift($saved, $created);
            $this->session->set_userdata('warga_demo_requests', $saved);
            return array('success' => TRUE, 'id' => $id);
        }

        $service = $this->db->where(array('slug' => $serviceSlug, 'is_active' => 1))->get('service_types')->row_array();
        if (!$service) return array('success' => FALSE, 'message' => 'Jenis layanan tidak ditemukan.');
        if (empty($user['village_id'])) return array('success' => FALSE, 'message' => 'Akun belum terhubung ke desa.');
        $id = warga_uuid();
        $requestCode = 'SDW-' . date('Y') . '-' . strtoupper(substr(str_replace('-', '', $id), 0, 8));
        $uploaded = $this->collect_uploaded_documents($id);
        if (!empty($uploaded['error'])) return array('success' => FALSE, 'message' => $uploaded['error']);
        $now = date('Y-m-d H:i:s');
        $documentMeta = array();
        foreach ($uploaded['files'] as &$file) {
            $file['id'] = warga_uuid();
            $documentMeta[] = array(
                'id' => $file['id'],
                'original_name' => $file['original_name'],
                'mime_type' => $file['mime_type'],
                'file_size' => (int) $file['file_size']
            );
        }
        unset($file);
        // The local SmartDesa inbox needs one self-contained message. The
        // central database remains the source of the actual documents.
        $payload = json_encode(array(
            'request_id' => $id,
            'request_code' => $requestCode,
            'service_type_id' => (int) $service['id'],
            'service_slug' => $service['slug'],
            'service_name' => $service['name'],
            'status' => 'submitted',
            'purpose' => $purpose,
            'note' => $note,
            'citizen_name' => $user['name'],
            'citizen_phone' => $user['phone'],
            'document_count' => count($documentMeta),
            'documents' => $documentMeta,
            'submitted_at' => $now
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->trans_start();
        $this->db->insert('service_requests', array('id' => $id, 'request_code' => $requestCode, 'citizen_user_id' => (int) $user['id'], 'village_id' => $user['village_id'], 'service_type_id' => (int) $service['id'], 'status' => 'submitted', 'payload_json' => $payload, 'local_sync_status' => 'pending', 'submitted_at' => $now));
        $this->db->insert('request_status_history', array('request_id' => $id, 'to_status' => 'submitted', 'note' => 'Permohonan diajukan warga.', 'actor_id' => (int) $user['id']));
        foreach ($uploaded['files'] as $file) $this->db->insert('request_documents', array('id' => $file['id'], 'request_id' => $id, 'original_name' => $file['original_name'], 'stored_name' => $file['stored_name'], 'storage_path' => $file['storage_path'], 'mime_type' => $file['mime_type'], 'file_size' => $file['file_size'], 'uploaded_by' => (int) $user['id']));
        $this->db->insert('sync_messages', array('id' => warga_uuid(), 'village_id' => $user['village_id'], 'aggregate_type' => 'service_request', 'aggregate_id' => $id, 'direction' => 'cloud_to_local', 'operation' => 'upsert', 'payload_json' => $payload, 'status' => 'pending', 'idempotency_key' => 'request:' . $id));
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Permohonan belum dapat disimpan.');
        }
        return array('success' => TRUE, 'id' => $id);
    }

    public function documents_for_user($requestId, $userId)
    {
        if (warga_demo_mode() || !warga_database_available()) return array();
        return $this->db->select('d.id,d.original_name,d.mime_type,d.file_size')->from('request_documents d')->join('service_requests r', 'r.id=d.request_id')
            ->where(array('d.request_id' => (string) $requestId, 'r.citizen_user_id' => (int) $userId))->order_by('d.created_at', 'ASC')->get()->result_array();
    }

    public function official_document_for_user($requestId, $userId)
    {
        if (warga_demo_mode() || !warga_database_available()) return NULL;
        return $this->db->select('r.document_path')->from('service_requests r')->where(array('r.id' => (string) $requestId, 'r.citizen_user_id' => (int) $userId, 'r.status' => 'issued'))->get()->row_array();
    }
}
