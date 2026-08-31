<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $requests = $this->Request_model->for_user($this->currentUser['id']);
        $notifications = array();
        foreach (array_slice($requests, 0, 8) as $row) {
            $notifications[] = array(
                'title' => $row['service_name'],
                'message' => $row['status'] === 'issued' ? 'Surat Anda sudah diterbitkan.' : 'Status terakhir permohonan: ' . warga_status_text($row['status']) . '.',
                'occurred_at' => $row['updated_at'],
                'request_id' => $row['id'],
                'status' => $row['status']
            );
        }
        $this->render('notifications/index', array('pageTitle' => 'Notifikasi', 'notifications' => $notifications));
    }
}
