<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Layanan extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $listing = $this->Request_model->paginated_service_types(
            isset($this->currentUser['village_id']) ? $this->currentUser['village_id'] : '',
            array(
                'q' => $this->input->get('q', TRUE),
                'page' => $this->input->get('page', TRUE)
            ),
            20
        );
        $data = array(
            'pageTitle' => 'Semua Surat',
            'showBackButton' => TRUE,
            'backUrl' => site_url('dashboard'),
            'services' => $listing['items'],
            'listing' => $listing,
            'listUrl' => site_url('layanan'),
            'citizenVerified' => $this->Auth_model->citizen_is_verified((int) $this->currentUser['id'])
        );
        $this->output->set_header('Cache-Control: no-store, private');
        if ($this->input->is_ajax_request()) {
            return $this->json(array(
                'html' => $this->load->view('services/results', $data, TRUE),
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
                'filters' => $listing['filters']
            ));
        }
        $this->render('services/index', $data);
    }
}
