<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Community_model extends CI_Model
{
    private $readyState = NULL;

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
        $this->db->select('a.*, u.name AS author_name')->from('warga_announcements a')->join('users u', 'u.id=a.author_id');
        $this->db->where('a.village_id', $user['village_id']);
        if (!$this->can_manage($user)) $this->db->where('a.status', 'published');
        return $this->db->order_by('a.created_at', 'DESC')->limit($limit)->get()->result_array();
    }

    public function announcement($id, array $user)
    {
        if (!$this->ready() || empty($user['village_id'])) return null;
        $this->db->select('a.*, u.name AS author_name')->from('warga_announcements a')->join('users u', 'u.id=a.author_id')
            ->where(array('a.id' => $id, 'a.village_id' => $user['village_id']));
        if (!$this->can_manage($user)) $this->db->where('a.status', 'published');
        return $this->db->get()->row_array();
    }

    public function publish(array $user, $title, $body)
    {
        if (!$this->ready() || !$this->can_manage($user)) return false;
        $id = warga_uuid();
        $this->db->trans_start();
        $this->db->insert('warga_announcements', array('id' => $id, 'village_id' => $user['village_id'],
            'author_id' => $user['id'], 'title' => $title, 'body' => $body));
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
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id : false;
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
        $this->db->trans_commit();
        return true;
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
