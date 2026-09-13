<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Push_worker extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) show_404();
    }

    public function run()
    {
        $this->load->model('Notification_model');
        if (!$this->Notification_model->ready()) { fwrite(STDERR, "Jalankan migrasi 013 terlebih dahulu.\n"); exit(1); }
        $public = trim((string)getenv('WARGA_VAPID_PUBLIC_KEY'));
        $private = trim((string)getenv('WARGA_VAPID_PRIVATE_KEY'));
        $subject = trim((string)getenv('WARGA_VAPID_SUBJECT'));
        if ($public === '' || $private === '' || $subject === '') { fwrite(STDERR, "Konfigurasi VAPID belum lengkap.\n"); exit(1); }
        // User-visible service updates should be delivered promptly even when
        // Android is in Doze. Keep the setting configurable for installations
        // that deliberately prefer a lower delivery priority.
        $urgency = strtolower(trim((string)getenv('WARGA_PUSH_URGENCY')));
        if (!in_array($urgency, array('very-low', 'low', 'normal', 'high'), true)) $urgency = 'high';
        $verbose = getenv('WARGA_PUSH_DEBUG') === '1'
            || (defined('STDIN') && isset($_SERVER['argv']) && in_array('--verbose', $_SERVER['argv'], true));
        $lockName = 'warga-push-' . substr(hash('sha256', $this->db->database),0,32);
        $lock = $this->db->query('SELECT GET_LOCK(?,0) AS acquired',array($lockName))->row_array();
        if (empty($lock['acquired'])) return;
        try {
            $webPush = new \Minishlink\WebPush\WebPush(array('VAPID'=>array('subject'=>$subject,'publicKey'=>$public,'privateKey'=>$private)),
                array('TTL'=>86400,'urgency'=>$urgency), 8, array('allow_redirects'=>false,'connect_timeout'=>4));
            $this->db->query("INSERT IGNORE INTO warga_push_deliveries (notification_id,subscription_id)
                SELECT n.id,s.id FROM notifications n JOIN warga_push_subscriptions s ON s.user_id=n.user_id
                JOIN users u ON u.id=n.user_id
                WHERE u.is_active=1 AND n.read_at IS NULL AND n.created_at>=s.created_at
                AND n.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)");
            $rows = $this->db->select('d.*,s.endpoint,s.endpoint_hash,s.public_key,s.auth_token,n.user_id,n.request_id,t.target_path,r.slug AS role_slug')
                ->from('warga_push_deliveries d')->join('warga_push_subscriptions s','s.id=d.subscription_id')
                ->join('notifications n','n.id=d.notification_id')->join('users u','u.id=n.user_id')
                ->join('roles r','r.id=u.role_id')->join('warga_notification_targets t','t.notification_id=n.id','left')
                ->where(array('d.status'=>'pending','u.is_active'=>1))->where('n.read_at',null)
                ->where('d.next_attempt_at <=',date('Y-m-d H:i:s'))->where('d.attempts <',5)
                ->where('n.created_at >=',date('Y-m-d H:i:s',time()-7*86400))->order_by('n.created_at','ASC')->limit(40)->get()->result_array();
            $sent=0; $failed=0; $expired=0; $invalid=0; $started=microtime(true); $unreadCounts=array();
            if ($verbose) fwrite(STDOUT, "Push worker: prioritas={$urgency}, antrean=" . count($rows) . "\n");
            foreach ($rows as $row) {
                if (microtime(true)-$started>45) break;
                $key=array('notification_id'=>$row['notification_id'],'subscription_id'=>$row['subscription_id']);
                if (!$this->Notification_model->valid_endpoint($row['endpoint'])) {
                    $invalid++;
                    if ($verbose) fwrite(STDERR, 'Push endpoint tidak valid: subscription=' . (int)$row['subscription_id'] . ' hash=' . substr((string)$row['endpoint_hash'], -8) . "\n");
                    $this->db->where('id',$row['subscription_id'])->delete('warga_push_subscriptions'); continue;
                }
                try {
                    $subscription = \Minishlink\WebPush\Subscription::create(array('endpoint'=>$row['endpoint'],
                        'publicKey'=>$row['public_key'],'authToken'=>$row['auth_token'],'contentEncoding'=>'aes128gcm'));
                    // Keep personal details and complaint content off the lock screen.
                    // Route through the notification opener so a panel click marks this
                    // exact notification as read before redirecting to its detail page.
                    // Send a scope-relative route instead of an absolute URL.  This
                    // worker runs from CLI, where a stale APP_URL can otherwise put
                    // the wrong host or installation path into an already delivered
                    // push.  The service worker resolves this route against its own
                    // registered scope before opening it.
                    $userId=(int)$row['user_id'];
                    if (!array_key_exists($userId,$unreadCounts)) {
                        $unreadCounts[$userId]=(int)$this->db->where('user_id',$userId)->where('read_at',null)->count_all_results('notifications');
                    }
                    $openPath = 'notifikasi/buka/' . rawurlencode((string)$row['notification_id']);
                    $payload=json_encode(array('title'=>'SI DAPULIK','body'=>'Ada pembaruan layanan untuk Anda.',
                        'tag'=>'sdw-'.$row['notification_id'],'url'=>$openPath,
                        'notificationId'=>(string)$row['notification_id'],
                        'unreadCount'=>$unreadCounts[$userId]), JSON_UNESCAPED_SLASHES);
                    if (!is_string($payload)) throw new RuntimeException('Payload push tidak valid.');
                    $report=$webPush->sendOneNotification($subscription,$payload);
                    if ($report->isSubscriptionExpired()) {
                        $expired++;
                        if ($verbose) fwrite(STDERR, 'Push endpoint kedaluwarsa: subscription=' . (int)$row['subscription_id'] . ' hash=' . substr((string)$row['endpoint_hash'], -8) . "\n");
                        $this->db->where('id',$row['subscription_id'])->delete('warga_push_subscriptions'); continue;
                    }
                    if (!$report->isSuccess()) {
                        $reason = method_exists($report, 'getReason') ? trim((string)$report->getReason()) : 'Push belum diterima.';
                        $status = $report->getResponse() ? (int)$report->getResponse()->getStatusCode() : 0;
                        throw new RuntimeException($reason . ($status ? ' (HTTP ' . $status . ')' : ''));
                    }
                    $this->db->where($key)->update('warga_push_deliveries',array('status'=>'sent','attempts'=>(int)$row['attempts']+1));
                    $sent++;
                    if ($verbose) fwrite(STDOUT, 'Push diterima provider: notification=' . $row['notification_id'] . ' subscription=' . (int)$row['subscription_id'] . ' hash=' . substr((string)$row['endpoint_hash'], -8) . "\n");
                } catch (Throwable $e) {
                    $attempts=(int)$row['attempts']+1;
                    $this->db->where($key)->update('warga_push_deliveries',array('attempts'=>$attempts,
                        'status'=>$attempts>=5?'failed':'pending','next_attempt_at'=>date('Y-m-d H:i:s',time()+60*(2**$attempts))));
                    $failed++;
                    if ($verbose) fwrite(STDERR, 'Push gagal: notification=' . $row['notification_id'] . ' subscription=' . (int)$row['subscription_id'] . ' hash=' . substr((string)$row['endpoint_hash'], -8) . ' ' . $e->getMessage() . "\n");
                }
            }
            echo "Push terkirim: ".$sent.", dicoba ulang/gagal: ".$failed."";
            if ($expired || $invalid) echo ", kedaluwarsa: " . $expired . ", tidak valid: " . $invalid;
            echo "\n";
        } finally { $this->db->query('SELECT RELEASE_LOCK(?)',array($lockName)); }
    }
}
