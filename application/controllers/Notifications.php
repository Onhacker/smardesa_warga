<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $listing = $this->Request_model->paginated_for_user($this->currentUser['id'], array(
            'q' => $this->input->get('q', TRUE),
            'date' => $this->input->get('date', TRUE),
            'page' => $this->input->get('page', TRUE)
        ), 'updated_at');
        $notifications = array();
        foreach ($listing['items'] as $row) {
            $notifications[] = array(
                'title' => $row['service_name'],
                'message' => $row['status'] === 'issued' ? 'Surat Anda sudah diterbitkan.' : 'Status terakhir permohonan: ' . warga_status_text($row['status']) . '.',
                'occurred_at' => $row['updated_at'],
                'request_id' => $row['id'],
                'status' => $row['status'],
                'service_slug' => $row['service_slug'],
                'service_name' => $row['service_name']
            );
        }
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
}
