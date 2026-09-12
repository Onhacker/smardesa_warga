<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        // The authenticated dashboard contains tenant-specific counts and
        // announcement summaries.  Never let a browser/proxy reuse one
        // village's HTML after logout or when another resident signs in.
        $this->output->set_header('Cache-Control: no-store, private');
        $this->output->set_header('Pragma: no-cache');
    }

    public function index()
    {
        if ($this->currentUser && warga_is_staff($this->currentUser)) redirect('petugas');
        $this->load->model('Community_model');
        $this->load->model('Marketplace_model', 'marketplace');
        $this->load->model('Request_model');
        if ($this->currentUser) {
            $summary = $this->Request_model->summary($this->currentUser['id']);
            $village = $this->Community_model->village($this->currentUser['village_id'], $this->currentUser['village_name'] ?? '');
            $announcements = $this->Community_model->announcements($this->currentUser, 3);
            $services = $this->Request_model->service_types($this->currentUser['village_id'] ?? '');
        } else {
            $summary = array('total' => 0, 'active' => 0, 'issued' => 0, 'revision' => 0);
            $village = array(
                'name' => getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya',
                'district_name' => getenv('PUBLIC_DISTRICT_NAME') ?: '',
                'regency_name' => getenv('PUBLIC_REGENCY_NAME') ?: (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya'),
                'institution' => getenv('PUBLIC_INSTITUTION_LABEL') ?: 'Kampung',
                'contact' => array()
            );
            // Announcement content is tenant-private. Guests may still use
            // the other public services, but never receive a count or item
            // sourced from another village.
            $announcements = array();
            // Guests may preview the public service catalogue. Protected
            // actions still send them through the normal login flow.
            $services = $this->Request_model->service_types('');
        }
        // Keep the dashboard preview lightweight while the public catalogue
        // remains available from the dedicated Pasar page.  The marketplace
        // model applies the same published/public visibility rules here.
        $marketListing = $this->marketplace->latest_public_products(4);
        $this->render('community/home', array('pageTitle'=>'Beranda',
            'village'=>$village, 'summary'=>$summary, 'announcements'=>$announcements,
            'services'=>array_slice(is_array($services) ? $services : array(), 0, 4),
            'marketplaceProducts'=>array_slice(isset($marketListing['items']) && is_array($marketListing['items']) ? $marketListing['items'] : array(), 0, 4),
            'marketplaceReady'=>!isset($marketListing['ready']) || (bool) $marketListing['ready']));
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
