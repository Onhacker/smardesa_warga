<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Public_Controller
{
    public function index()
    {
        $this->load->model('Community_model');
        if ($this->currentUser) {
            $this->load->model('Request_model');
            $summary = $this->Request_model->summary($this->currentUser['id']);
            $village = $this->Community_model->village($this->currentUser['village_id'], $this->currentUser['village_name'] ?? '');
            $announcements = $this->Community_model->announcements($this->currentUser, 3);
        } else {
            $summary = array('total' => 0, 'active' => 0, 'issued' => 0, 'revision' => 0);
            $village = array('name' => getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya', 'institution' => getenv('PUBLIC_INSTITUTION_LABEL') ?: 'Kampung', 'contact' => array());
            $announcements = $this->Community_model->public_announcements(3);
        }
        $this->render('community/home', array('pageTitle'=>'Beranda',
            'village'=>$village, 'summary'=>$summary, 'announcements'=>$announcements));
    }

    public function letters()
    {
        $this->require_authentication();
        if (warga_is_staff($this->currentUser)) redirect('petugas');
        $this->load->model('Request_model');
        $requests = $this->Request_model->for_user($this->currentUser['id']);
        $services = $this->Request_model->service_types(isset($this->currentUser['village_id']) ? $this->currentUser['village_id'] : '');
        $this->render('dashboard/index', array(
            'pageTitle' => 'Surat',
            'requests' => $requests,
            'summary' => $this->Request_model->summary($this->currentUser['id']),
            'services' => array_slice($services, 0, 8),
            'citizenVerified' => $this->Auth_model->citizen_is_verified((int) $this->currentUser['id']),
            'lettersPage' => TRUE
        ));
    }
}
