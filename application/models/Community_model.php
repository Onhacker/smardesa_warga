<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Community_model extends CI_Model
{
    private $readyState = NULL;
    private $attachmentSchemaState = NULL;
    private $lastError = '';

    public function last_error()
    {
        return $this->lastError;
    }

    public function ready()
    {
        if ($this->readyState !== NULL) return $this->readyState;
        return $this->readyState = warga_database_available() && $this->db->table_exists('warga_announcements')
            && $this->db->table_exists('warga_notification_targets');
    }

    public function can_manage(array $user)
    {
        return !empty($user['village_id']) && in_array($user['role_slug'] ?? '', array('sekdes', 'kepala-desa'), true);
    }

    /**
     * Attachments are deployed by migration, never created during a request.
     * Keep this capability check separate so older installations can still
     * read and publish announcements without an attachment.
     */
    private function attachment_schema_ready()
    {
        if ($this->attachmentSchemaState !== NULL) return $this->attachmentSchemaState;
        return $this->attachmentSchemaState = warga_database_available()
            && $this->db->table_exists('warga_announcement_attachments');
    }

    private function announcement_query_base()
    {
        $hasAttachmentSchema = $this->attachment_schema_ready();
        // Only expose the author's role to the view.  Personal author names are
        // deliberately not selected because public announcements use a jabatan
        // label (Kepala/Sekretaris bentuk lembaga), not a person's name.
        $this->db->select('a.*, r.name AS author_role_name, r.slug AS author_role_slug')
            ->from('warga_announcements a')
            ->join('users u', 'u.id=a.author_id', 'left')
            ->join('roles r', 'r.id=u.role_id', 'left');
        if ($hasAttachmentSchema) {
            $this->db->select('aa.id AS attachment_id, aa.original_name AS attachment_original_name,
                aa.mime_type AS attachment_mime_type, aa.file_size AS attachment_file_size', FALSE)
                ->join('warga_announcement_attachments aa', 'aa.announcement_id=a.id AND aa.village_id=a.village_id', 'left');
        }
    }

    private function with_attachment_aliases(array $row)
    {
        if (!empty($row['attachment_id'])) {
            $row['attachment'] = array(
                'id' => (string) $row['attachment_id'],
                'original_name' => (string) ($row['attachment_original_name'] ?? ''),
                'mime_type' => (string) ($row['attachment_mime_type'] ?? ''),
                'file_size' => (int) ($row['attachment_file_size'] ?? 0)
            );
        } else {
            $row['attachment'] = NULL;
        }
        return $row;
    }

    public function village($id, $fallbackName = '')
    {
        $row = warga_database_available() ? $this->db->where('id', (string) $id)->get('village_tenants')->row_array() : array();
        $settings = json_decode((string) ($row['settings_json'] ?? ''), true);
        $row['settings'] = is_array($settings) ? $settings : array();
        $row['contact'] = isset($row['settings']['contact']) && is_array($row['settings']['contact']) ? $row['settings']['contact'] : array();
        // The sync API stores the canonical bentuk_lembaga value in contact.institution.
        // Keep the newer aliases as compatibility fallbacks for older tenant snapshots.
        $institution = trim((string) ($row['contact']['institution'] ?? ''));
        if ($institution === '') {
            $identity = isset($row['settings']['identitas_desa']) && is_array($row['settings']['identitas_desa'])
                ? $row['settings']['identitas_desa']
                : (isset($row['settings']['identitas_desa']) && is_object($row['settings']['identitas_desa'])
                    ? (array) $row['settings']['identitas_desa'] : array());
            $institution = trim((string) ($row['settings']['bentuk_lembaga']
                ?? ($row['settings']['identity']['bentuk_lembaga']
                    ?? ($row['settings']['identitas']['bentuk_lembaga']
                        ?? ($identity['bentuk_lembaga'] ?? '')))));
        }
        $institutionName = trim((string) ($row['name'] ?? ''));
        if ($institutionName === '') {
            $institutionName = trim((string) $fallbackName);
        }
        if ($institution === '') $institution = warga_institution_label($institutionName, 'Desa');
        $row['institution'] = $institution !== '' ? $institution : 'Desa';
        return $row;
    }

    private function institution_for_user(array $user)
    {
        $village = $this->village($user['village_id'] ?? '', $user['village_name'] ?? '');
        return trim((string) ($village['institution'] ?? 'Desa')) ?: 'Desa';
    }

    public function workflow($villageId)
    {
        require_once dirname(__DIR__) . '/libraries/Verification_workflow.php';
        $village = $this->village($villageId);
        return Verification_workflow::settings($village['settings']['verification'] ?? array());
    }

    public function notify($userId, $title, $message, $path, $requestId = null)
    {
        if (!$this->ready()) return;
        $id = warga_uuid();
        $this->db->insert('notifications', array('id' => $id, 'user_id' => $userId, 'request_id' => $requestId,
            'title' => mb_substr($title, 0, 180), 'message' => mb_substr($message, 0, 1000)));
        $this->db->insert('warga_notification_targets', array('notification_id' => $id, 'target_path' => $path));
    }

    public function notify_staff($villageId, $title, $message, $path, array $roles = array('sekdes', 'kepala-desa'), $requestId = null)
    {
        if (!$this->ready() || !$roles) return;
        $rows = $this->db->select('u.id')->from('users u')->join('roles r', 'r.id=u.role_id')
            ->where(array('u.village_id' => $villageId, 'u.is_active' => 1))->where_in('r.slug', $roles)->get()->result_array();
        foreach ($rows as $row) $this->notify($row['id'], $title, $message, $path, $requestId);
    }

    public function announcements(array $user, $limit = 30)
    {
        if (!$this->ready() || empty($user['village_id'])) return array();
        $this->announcement_query_base();
        $this->db->where('a.village_id', $user['village_id']);
        if (!$this->can_manage($user)) $this->db->where('a.status', 'published');
        $rows = $this->db->order_by('a.created_at', 'DESC')->limit($limit)->get()->result_array();
        return array_map(array($this, 'with_attachment_aliases'), $rows);
    }

    public function announcement($id, array $user)
    {
        if (!$this->ready() || empty($user['village_id'])) return null;
        $this->announcement_query_base();
        $this->db->where(array('a.id' => $id, 'a.village_id' => $user['village_id']));
        if (!$this->can_manage($user)) $this->db->where('a.status', 'published');
        $row = $this->db->get()->row_array();
        return $row ? $this->with_attachment_aliases($row) : NULL;
    }

    public function announcement_attachment($id, array $user)
    {
        if (!$this->attachment_schema_ready() || empty($user['village_id'])) return NULL;
        $this->db->select('aa.*')->from('warga_announcement_attachments aa')
            ->join('warga_announcements a', 'a.id=aa.announcement_id AND a.village_id=aa.village_id')
            ->where(array('aa.announcement_id' => (string) $id, 'aa.village_id' => $user['village_id']));
        if (!$this->can_manage($user)) $this->db->where('a.status', 'published');
        return $this->db->get()->row_array();
    }

    public function publish(array $user, $title, $body, $upload = NULL)
    {
        $this->lastError = '';
        if (!$this->ready() || !$this->can_manage($user)) {
            $this->lastError = 'Pengumuman belum dapat diterbitkan.';
            return false;
        }
        $hasUpload = is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasUpload && !$this->attachment_schema_ready()) {
            $this->lastError = 'Fitur lampiran belum siap. Jalankan migration 020_announcement_attachments.sql terlebih dahulu.';
            return false;
        }
        $id = warga_uuid();
        $attachment = NULL;
        $storedPaths = array();
        if ($hasUpload) {
            $prepared = $this->prepare_announcement_attachment($id, $upload);
            if (empty($prepared['success'])) {
                $this->lastError = (string) ($prepared['message'] ?? 'Lampiran belum dapat disimpan.');
                return false;
            }
            $attachment = $prepared['attachment'];
            $attachment['village_id'] = (string) $user['village_id'];
            $storedPaths = $prepared['paths'];
        }
        if (!$this->db->trans_begin()) {
            $this->cleanup_paths($storedPaths);
            $this->lastError = 'Pengumuman belum dapat dimulai. Silakan coba lagi.';
            return false;
        }
        $this->db->insert('warga_announcements', array('id' => $id, 'village_id' => $user['village_id'],
            'author_id' => $user['id'], 'title' => $title, 'body' => $body));
        if ($attachment) {
            $this->db->insert('warga_announcement_attachments', $attachment);
        }
        // INSERT SELECT keeps publication independent of village population size.
        $institution = $this->institution_for_user($user);
        $institutionLower = function_exists('mb_strtolower') ? mb_strtolower($institution, 'UTF-8') : strtolower($institution);
        $this->db->query("INSERT INTO notifications (id,user_id,title,message)
            SELECT MD5(CONCAT(?,u.id)),u.id,?,? FROM users u JOIN roles r ON r.id=u.role_id
            WHERE u.village_id=? AND u.is_active=1 AND r.slug='warga'",
            array($id, $title, 'Pengumuman baru dari pemerintah ' . $institutionLower . '.', $user['village_id']));
        // A stable announcement ID in targets also supports direct notification links.
        $this->db->query("INSERT INTO warga_notification_targets (notification_id,target_path)
            SELECT n.id,? FROM notifications n JOIN users u ON u.id=n.user_id
            LEFT JOIN warga_notification_targets t ON t.notification_id=n.id
            WHERE u.village_id=? AND n.id=MD5(CONCAT(?,u.id)) AND t.notification_id IS NULL",
            array('pengumuman/' . $id, $user['village_id'], $id));
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            $this->cleanup_paths($storedPaths);
            $this->lastError = 'Pengumuman belum dapat disimpan.';
            return false;
        }
        if (!$this->db->trans_commit()) {
            $this->cleanup_paths($storedPaths);
            $this->lastError = 'Pengumuman belum dapat disimpan.';
            return false;
        }
        return $id;
    }

    public function delete_announcement($id, array $user)
    {
        if (!$this->can_manage($user) || !$this->ready()) return false;
        $path = 'pengumuman/' . $id;
        $this->db->trans_begin();

        // Lock the tenant-scoped row first so another request cannot delete a
        // different tenant's announcement or leave its notification behind.
        $announcement = $this->db->query(
            'SELECT * FROM warga_announcements WHERE id=? AND village_id=? FOR UPDATE',
            array($id, $user['village_id'])
        )->row_array();
        if (!$announcement) {
            $this->db->trans_rollback();
            return false;
        }

        $attachmentPath = '';
        if ($this->attachment_schema_ready()) {
            $attachmentRow = $this->db->select('storage_path')->from('warga_announcement_attachments')
                ->where(array('announcement_id' => (string) $id, 'village_id' => $user['village_id']))
                ->get()->row_array();
            $attachmentPath = trim((string) ($attachmentRow['storage_path'] ?? ''));
        }

        // A notification target is shared by the notification inbox and the
        // announcement link. Remove only this tenant's matching notifications;
        // the FK then also cleans up push-delivery rows safely.
        $notificationRows = $this->db->select('n.id')->from('notifications n')
            ->join('warga_notification_targets t', 't.notification_id=n.id')
            ->join('users u', 'u.id=n.user_id')
            ->where(array('t.target_path' => $path, 'u.village_id' => $user['village_id']))
            ->get()->result_array();
        $notificationIds = array();
        foreach ($notificationRows as $row) $notificationIds[] = $row['id'];
        if ($notificationIds) $this->db->where_in('id', $notificationIds)->delete('notifications');

        $this->db->where(array('id' => $id, 'village_id' => $user['village_id']))->delete('warga_announcements');
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return false;
        }
        if (!$this->db->trans_commit()) return false;
        if ($attachmentPath !== '' && is_file($attachmentPath)) {
            @unlink($attachmentPath);
            $directory = dirname($attachmentPath);
            if (is_dir($directory)) @rmdir($directory);
        }
        return true;
    }

    private function prepare_announcement_attachment($announcementId, array $upload)
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) return array('success' => TRUE, 'attachment' => NULL, 'paths' => array());
        if ($error !== UPLOAD_ERR_OK) {
            $messages = array(
                UPLOAD_ERR_INI_SIZE => 'Lampiran melebihi batas ukuran server.',
                UPLOAD_ERR_FORM_SIZE => 'Lampiran melebihi batas ukuran formulir.',
                UPLOAD_ERR_PARTIAL => 'Lampiran hanya terunggah sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload belum tersedia.',
                UPLOAD_ERR_CANT_WRITE => 'Server belum dapat menulis lampiran.',
                UPLOAD_ERR_EXTENSION => 'Upload lampiran dihentikan oleh ekstensi server.'
            );
            return array('success' => FALSE, 'message' => $messages[$error] ?? 'Lampiran gagal diunggah.');
        }

        $tmp = trim((string) ($upload['tmp_name'] ?? ''));
        $size = (int) ($upload['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) return array('success' => FALSE, 'message' => 'Lampiran tidak valid. Silakan pilih file dari perangkat Anda.');
        if ($size < 1 || $size > 8 * 1024 * 1024) return array('success' => FALSE, 'message' => 'Lampiran harus berukuran maksimal 8 MB.');

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : NULL;
        if (!$finfo) return array('success' => FALSE, 'message' => 'Pemeriksaan jenis lampiran belum tersedia pada server.');
        $mime = strtolower(trim((string) finfo_file($finfo, $tmp)));
        finfo_close($finfo);
        $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf');
        if (!isset($allowed[$mime])) return array('success' => FALSE, 'message' => 'Lampiran hanya boleh berupa JPG, PNG, WebP, atau PDF.');
        if (strpos($mime, 'image/') === 0 && function_exists('getimagesize') && @getimagesize($tmp) === FALSE) {
            return array('success' => FALSE, 'message' => 'Isi gambar tidak dapat dibaca. Pilih gambar yang valid.');
        }

        $storage = $this->private_storage_path();
        if ($storage === NULL) return array('success' => FALSE, 'message' => 'Penyimpanan lampiran belum siap.');
        $directory = $storage . DIRECTORY_SEPARATOR . 'announcements' . DIRECTORY_SEPARATOR . (string) $announcementId;
        if (!is_dir($directory) && !@mkdir($directory, 0750, TRUE) && !is_dir($directory)) {
            return array('success' => FALSE, 'message' => 'Folder lampiran belum dapat dibuat.');
        }
        try {
            $random = bin2hex(random_bytes(12));
        } catch (Exception $exception) {
            $random = sha1(uniqid('', TRUE) . mt_rand());
        }
        $storedName = (string) $announcementId . '-' . $random . '.' . $allowed[$mime];
        $destination = $directory . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($tmp, $destination)) {
            @rmdir($directory);
            return array('success' => FALSE, 'message' => 'Lampiran belum dapat disimpan.');
        }
        @chmod($destination, 0640);

        $sha256 = hash_file('sha256', $destination);
        if (!is_string($sha256) || !preg_match('/^[a-f0-9]{64}$/D', $sha256)) {
            @unlink($destination);
            @rmdir($directory);
            return array('success' => FALSE, 'message' => 'Integritas lampiran belum dapat diperiksa.');
        }

        $originalName = basename(str_replace('\\', '/', (string) ($upload['name'] ?? 'lampiran')));
        $originalName = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $originalName);
        $originalName = trim((string) $originalName, " ._-\t\r\n");
        if ($originalName === '') $originalName = 'lampiran.' . $allowed[$mime];
        $originalName = substr($originalName, 0, 180);
        return array(
            'success' => TRUE,
            'paths' => array($destination),
            'attachment' => array(
                'id' => warga_uuid(),
                'announcement_id' => (string) $announcementId,
                'village_id' => '', // filled with the authenticated tenant by publish()
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'storage_path' => $destination,
                'mime_type' => $mime,
                'file_size' => $size,
                'sha256' => $sha256
            )
        );
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

    private function cleanup_paths(array $paths)
    {
        foreach ($paths as $path) {
            if (is_string($path) && is_file($path)) @unlink($path);
            if (is_string($path) && is_dir(dirname($path))) @rmdir(dirname($path));
        }
    }

    public function complaints(array $user)
    {
        if (!$this->ready() || empty($user['village_id'])) return array();
        $this->db->select('c.*,u.name AS citizen_name')->from('warga_complaints c')->join('users u','u.id=c.citizen_user_id')
            ->where('c.village_id', $user['village_id']);
        if (!$this->can_manage($user)) $this->db->where('c.citizen_user_id', $user['id']);
        return $this->db->order_by('c.updated_at','DESC')->limit(100)->get()->result_array();
    }

    public function complaint($id, array $user)
    {
        if (!$this->ready() || empty($user['village_id'])) return null;
        $this->db->select('c.*,u.name AS citizen_name')->from('warga_complaints c')->join('users u','u.id=c.citizen_user_id')
            ->where(array('c.id' => $id, 'c.village_id' => $user['village_id']));
        if (!$this->can_manage($user)) $this->db->where('c.citizen_user_id', $user['id']);
        return $this->db->get()->row_array();
    }

    public function submit_complaint(array $user, $title, $body, $location)
    {
        if (!$this->ready() || ($user['role_slug'] ?? '') !== 'warga') return false;
        $this->load->model('Auth_model');
        if (!$this->Auth_model->citizen_is_verified($user['id'], $user['village_id'])) return false;
        $recent = $this->db->where('citizen_user_id', $user['id'])->where('created_at >=', date('Y-m-d H:i:s', time()-86400))
            ->count_all_results('warga_complaints');
        if ($recent >= 10) return false;
        $id = warga_uuid();
        $this->db->trans_start();
        $this->db->insert('warga_complaints', array('id' => $id, 'village_id' => $user['village_id'],
            'citizen_user_id' => $user['id'], 'title' => $title, 'body' => $body, 'location' => $location));
        $this->notify_staff($user['village_id'], 'Pengaduan warga baru', 'Pengaduan baru menunggu tindak lanjut.', 'pengaduan/' . $id);
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : false;
    }

    public function replies($complaintId)
    {
        return $this->db->select('r.*,u.name AS actor_name')->from('warga_complaint_replies r')->join('users u','u.id=r.actor_id')
            ->where('r.complaint_id', $complaintId)->order_by('r.id','ASC')->get()->result_array();
    }

    public function reply($id, array $user, $message, $status)
    {
        $row = $this->complaint($id, $user);
        if (!$row || !$this->can_manage($user) || !in_array($status, array('received','processing','resolved','rejected'), true)) return false;
        $this->db->trans_begin();
        $this->db->query('SELECT id FROM warga_complaints WHERE id=? AND village_id=? FOR UPDATE', array($id,$user['village_id']));
        $this->db->where('id', $id)->update('warga_complaints', array('status' => $status, 'updated_at' => date('Y-m-d H:i:s')));
        $this->db->insert('warga_complaint_replies', array('complaint_id' => $id, 'actor_id' => $user['id'], 'message' => $message, 'status' => $status));
        $institution = $this->institution_for_user($user);
        $institutionLower = function_exists('mb_strtolower') ? mb_strtolower($institution, 'UTF-8') : strtolower($institution);
        $this->notify($row['citizen_user_id'], 'Tanggapan pengaduan', 'Pengaduan Anda mendapat tanggapan dari ' . $institutionLower . '.', 'pengaduan/' . $id);
        if (!$this->db->trans_status()) { $this->db->trans_rollback(); return false; }
        $this->db->trans_commit();
        return true;
    }
}
