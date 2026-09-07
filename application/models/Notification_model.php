<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model
{
    public function ready()
    {
        return warga_database_available() && $this->db->table_exists('warga_notification_targets');
    }

    public function listing(array $user, array $filters = array())
    {
        $q = mb_substr(trim((string)($filters['q'] ?? '')),0,180);
        $date = trim((string)($filters['date'] ?? ''));
        $d = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) $date = '';
        $page = max(1,(int)($filters['page'] ?? 1));
        $where = function () use ($user,$q,$date) {
            $this->db->from('notifications n')->where('n.user_id',$user['id']);
            if ($q !== '') $this->db->group_start()->like('n.title',$q)->or_like('n.message',$q)->group_end();
            if ($date !== '') $this->db->where('n.created_at >=',$date.' 00:00:00')->where('n.created_at <=',$date.' 23:59:59');
        };
        $total = 0; $rows = array();
        if ($this->ready()) { $where(); $total = $this->db->count_all_results(); }
        $pages = max(1,(int)ceil($total/15)); $page = min($page,$pages);
        if ($total) {
            $where();
            $rows = $this->db->select('n.*, n.created_at AS occurred_at, t.target_path')->join('warga_notification_targets t','t.notification_id=n.id','left')
                ->order_by('n.created_at','DESC')->order_by('n.id','DESC')->limit(15,($page-1)*15)->get()->result_array();
            foreach ($rows as &$row) $row['target_path'] = $this->target($row, $user);
            unset($row);
        }
        return array('items'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>15,
            'from'=>$total ? ($page-1)*15+1 : 0,'to'=>min($page*15,$total),'filters'=>array('q'=>$q,'date'=>$date));
    }

    public function target(array $row, array $user)
    {
        $path = (string)($row['target_path'] ?? '');
        if (preg_match('~^(pengumuman|pengaduan|permohonan|petugas/permohonan)/[a-f0-9-]{32,36}$~D',$path)) return $path;
        if (!empty($row['request_id'])) return (warga_is_staff($user) ? 'petugas/permohonan/' : 'permohonan/').$row['request_id'];
        return 'notifikasi';
    }

    public function unread($userId)
    {
        return $this->ready() ? (int)$this->db->where('user_id',$userId)->where('read_at',null)->count_all_results('notifications') : 0;
    }

    public function read($userId)
    {
        if ($this->ready()) $this->db->where('user_id',$userId)->where('read_at',null)->update('notifications',array('read_at'=>date('Y-m-d H:i:s')));
    }

    /** Return one notification belonging to the signed-in user. */
    public function find_for_user($notificationId, $userId)
    {
        if (!$this->ready()) return null;
        return $this->db->select('n.*, t.target_path')
            ->from('notifications n')
            ->join('warga_notification_targets t', 't.notification_id=n.id', 'left')
            ->where(array('n.id' => (string) $notificationId, 'n.user_id' => (int) $userId))
            ->limit(1)->get()->row_array();
    }

    /** Mark only this user's notification as read, idempotently. */
    public function mark_read($userId, $notificationId)
    {
        if (!$this->ready()) return false;
        return $this->db->where(array('id' => (string) $notificationId, 'user_id' => (int) $userId))
            ->where('read_at', null)
            ->update('notifications', array('read_at' => date('Y-m-d H:i:s')));
    }

    public function subscribe($userId, array $data)
    {
        if (!$this->ready()) return false;
        $endpoint = (string)($data['endpoint'] ?? '');
        if (!$this->valid_endpoint($endpoint)) return false;
        $key = $data['keys']['p256dh'] ?? ''; $auth = $data['keys']['auth'] ?? '';
        if (!is_string($key) || !is_string($auth) || !preg_match('/^[A-Za-z0-9_-]{87}=?$/D',$key)
            || !preg_match('/^[A-Za-z0-9_-]{22}={0,2}$/D',$auth)) return false;
        $hash = hash('sha256',$endpoint);
        $existing = $this->db->where('endpoint_hash',$hash)->get('warga_push_subscriptions')->row_array();
        if ($existing && (int)$existing['user_id'] !== (int)$userId) {
            $this->db->where('id',$existing['id'])->delete('warga_push_subscriptions');
            $existing = null;
        }
        $row = array('user_id'=>$userId,'endpoint_hash'=>$hash,'endpoint'=>$endpoint,'public_key'=>$key,'auth_token'=>$auth);
        if ($existing) return $this->db->where('id',$existing['id'])->update('warga_push_subscriptions',$row);
        if ($this->db->where('user_id',$userId)->count_all_results('warga_push_subscriptions') >= 10) return false;
        return $this->db->insert('warga_push_subscriptions',$row);
    }

    public function valid_endpoint($endpoint)
    {
        $parts = parse_url($endpoint);
        if (strlen($endpoint)>2048 || !is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['port']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) return false;
        $host = strtolower($parts['host'] ?? '');
        // Only browser push services, never arbitrary user-supplied destinations.
        return in_array($host,array('fcm.googleapis.com','updates.push.services.mozilla.com','web.push.apple.com'),true)
            || (bool)preg_match('/^[a-z0-9-]+\.notify\.windows\.com$/D',$host);
    }
}
