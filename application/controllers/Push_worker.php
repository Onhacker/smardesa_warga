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
        $lockName = 'warga-push-' . substr(hash('sha256', $this->db->database),0,32);
        $lock = $this->db->query('SELECT GET_LOCK(?,0) AS acquired',array($lockName))->row_array();
        if (empty($lock['acquired'])) return;
        try {
            $webPush = new \Minishlink\WebPush\WebPush(array('VAPID'=>array('subject'=>$subject,'publicKey'=>$public,'privateKey'=>$private)),
                array('TTL'=>86400,'urgency'=>'normal'), 8, array('allow_redirects'=>false,'connect_timeout'=>4));
            $this->db->query("INSERT IGNORE INTO warga_push_deliveries (notification_id,subscription_id)
                SELECT n.id,s.id FROM notifications n JOIN warga_push_subscriptions s ON s.user_id=n.user_id
                JOIN users u ON u.id=n.user_id
                WHERE u.is_active=1 AND n.read_at IS NULL AND n.created_at>=s.created_at
                AND n.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)");
            $rows = $this->db->select('d.*,s.endpoint,s.public_key,s.auth_token,n.user_id,n.request_id,t.target_path,r.slug AS role_slug')
                ->from('warga_push_deliveries d')->join('warga_push_subscriptions s','s.id=d.subscription_id')
                ->join('notifications n','n.id=d.notification_id')->join('users u','u.id=n.user_id')
                ->join('roles r','r.id=u.role_id')->join('warga_notification_targets t','t.notification_id=n.id','left')
                ->where(array('d.status'=>'pending','u.is_active'=>1))->where('n.read_at',null)
                ->where('d.next_attempt_at <=',date('Y-m-d H:i:s'))->where('d.attempts <',5)
                ->where('n.created_at >=',date('Y-m-d H:i:s',time()-7*86400))->order_by('n.created_at','ASC')->limit(40)->get()->result_array();
            $sent=0; $failed=0; $started=microtime(true);
            foreach ($rows as $row) {
                if (microtime(true)-$started>45) break;
                $key=array('notification_id'=>$row['notification_id'],'subscription_id'=>$row['subscription_id']);
                if (!$this->Notification_model->valid_endpoint($row['endpoint'])) {
                    $this->db->where('id',$row['subscription_id'])->delete('warga_push_subscriptions'); continue;
                }
                try {
                    $subscription = \Minishlink\WebPush\Subscription::create(array('endpoint'=>$row['endpoint'],
                        'publicKey'=>$row['public_key'],'authToken'=>$row['auth_token'],'contentEncoding'=>'aes128gcm'));
                    // Keep personal details and complaint content off the lock screen.
                    $payload=json_encode(array('title'=>'SmartDesa Warga','body'=>'Ada pembaruan layanan untuk Anda.',
                        'tag'=>'sdw-'.$row['notification_id'],'url'=>site_url($this->Notification_model->target($row,$row))));
                    $report=$webPush->sendOneNotification($subscription,$payload);
                    if ($report->isSubscriptionExpired()) {
                        $this->db->where('id',$row['subscription_id'])->delete('warga_push_subscriptions'); continue;
                    }
                    if (!$report->isSuccess()) throw new RuntimeException('Push belum diterima.');
                    $this->db->where($key)->update('warga_push_deliveries',array('status'=>'sent','attempts'=>(int)$row['attempts']+1));
                    $sent++;
                } catch (Throwable $e) {
                    $attempts=(int)$row['attempts']+1;
                    $this->db->where($key)->update('warga_push_deliveries',array('attempts'=>$attempts,
                        'status'=>$attempts>=5?'failed':'pending','next_attempt_at'=>date('Y-m-d H:i:s',time()+60*(2**$attempts))));
                    $failed++;
                }
            }
            echo "Push terkirim: ".$sent.", dicoba ulang/gagal: ".$failed."\n";
        } finally { $this->db->query('SELECT RELEASE_LOCK(?)',array($lockName)); }
    }
}
