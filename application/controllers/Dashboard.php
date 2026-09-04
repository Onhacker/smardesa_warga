<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $requests = $this->Request_model->for_user($this->currentUser['id']);
        $services = $this->Request_model->service_types(isset($this->currentUser['village_id']) ? $this->currentUser['village_id'] : '');
        $this->render('dashboard/index', array(
            'pageTitle' => 'Beranda',
            'requests' => $requests,
            'summary' => $this->Request_model->summary($this->currentUser['id']),
            'services' => array_slice($services, 0, 8),
            'citizenVerified' => $this->Auth_model->citizen_is_verified((int) $this->currentUser['id'])
        ));
    }
}
