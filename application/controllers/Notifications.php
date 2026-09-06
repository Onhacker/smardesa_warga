<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends Public_Controller
{
    public function index()
    {
        // Keep the notification page protected while allowing the lightweight
        // summary endpoint below to answer an unauthenticated background poll
        // without redirecting it to the login page.
        $this->require_authentication();
        $this->load->model('Notification_model');
        $listing = $this->Notification_model->listing($this->currentUser, array(
            'q' => $this->input->get('q', TRUE),
            'date' => $this->input->get('date', TRUE),
            'page' => $this->input->get('page', TRUE)
        ));
        $notifications = $listing['items'];
        $data = array('pageTitle' => 'Notifikasi', 'notifications' => $notifications, 'listing' => $listing, 'listUrl' => site_url('notifikasi'));
        $this->output->set_header('Cache-Control: no-store, private');
        if ($this->input->is_ajax_request()) {
            return $this->json(array(
                'html' => $this->load->view('notifications/results', $data, TRUE),
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
                'filters' => $listing['filters']
            ));
        }
        $this->render('notifications/index', $data);
    }

    public function summary()
    {
        if (!$this->currentUser || empty($this->currentUser['id'])) {
            // This endpoint is polled from public pages.  Do not call
            // require_authentication() here: doing so stores this JSON URL as
            // intended_url and the next successful login would open raw JSON.
            return $this->json(array(
                'success' => FALSE,
                'message' => 'Sesi login diperlukan.',
                'login_url' => site_url('login')
            ), 401);
        }
        $this->load->model('Notification_model');
        $this->output->set_header('Cache-Control: no-store, private');
        return $this->json(array('unread' => $this->Notification_model->unread($this->currentUser['id'])));
    }

    public function read()
    {
        $this->require_authentication();
        $this->require_post();
        $this->load->model('Notification_model');
        $this->Notification_model->read($this->currentUser['id']);
        redirect('notifikasi');
    }

    public function subscribe()
    {
        if (!$this->require_json_authentication()) return;
        $this->require_post();
        $this->load->model('Notification_model');
        $raw = $this->input->post('subscription');
        $data = is_string($raw) && strlen($raw) < 5000 ? json_decode($raw,true) : null;
        $ok = is_array($data) && $this->Notification_model->subscribe($this->currentUser['id'],$data);
        return $this->json(array('success'=>$ok), $ok ? 200 : 422);
    }

    public function unsubscribe()
    {
        if (!$this->require_json_authentication()) return;
        $this->require_post();
        $this->load->model('Notification_model');
        if ($this->Notification_model->ready()) {
            $this->db->where(array('user_id'=>$this->currentUser['id'],
                'endpoint_hash'=>hash('sha256',(string)$this->input->post('endpoint'))))->delete('warga_push_subscriptions');
        }
        return $this->json(array('success'=>true));
    }

    /**
     * Return a machine-readable auth failure for notification mutations.
     * Browser page requests still use the normal login redirect elsewhere.
     */
    private function require_json_authentication()
    {
        if ($this->currentUser && !empty($this->currentUser['id'])) return TRUE;
        $this->json(array(
            'success' => FALSE,
            'message' => 'Sesi login diperlukan.',
            'login_url' => site_url('login')
        ), 401);
        return FALSE;
    }
}
