<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Community_model');
        $this->load->model('Request_model');
        $this->render('community/home', array('pageTitle'=>'Beranda',
            'village'=>$this->Community_model->village($this->currentUser['village_id']),
            'summary'=>$this->Request_model->summary($this->currentUser['id']),
            'announcements'=>$this->Community_model->announcements($this->currentUser,3)));
    }

    public function letters()
    {
        $this->load->model('Request_model');
        $requests = $this->Request_model->for_user($this->currentUser['id']);
        $services = $this->Request_model->service_types(isset($this->currentUser['village_id']) ? $this->currentUser['village_id'] : '');
        $this->render('dashboard/index', array(
            'pageTitle' => 'Surat',
            'requests' => $requests,
            'summary' => $this->Request_model->summary($this->currentUser['id']),
            'services' => array_slice($services, 0, 8),
            'citizenVerified' => $this->Auth_model->citizen_is_verified((int) $this->currentUser['id'])
        ));
    }
}
