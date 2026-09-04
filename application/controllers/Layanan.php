<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Layanan extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $this->render('services/index', array(
            'pageTitle' => 'Semua Layanan',
            'showBackButton' => TRUE,
            'backUrl' => site_url('dashboard'),
            'services' => $this->Request_model->service_types(isset($this->currentUser['village_id']) ? $this->currentUser['village_id'] : ''),
            'citizenVerified' => $this->Auth_model->citizen_is_verified((int) $this->currentUser['id'])
        ));
    }
}
