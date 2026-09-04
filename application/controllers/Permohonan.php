<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permohonan extends Citizen_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $listing = $this->Request_model->paginated_for_user($this->currentUser['id'], array(
            'q' => $this->input->get('q', TRUE),
            'date' => $this->input->get('date', TRUE),
            'status' => $this->input->get('status', TRUE),
            'page' => $this->input->get('page', TRUE)
        ));
        $data = array('pageTitle' => 'Permohonan Saya', 'requests' => $listing['items'], 'listing' => $listing, 'listUrl' => site_url('permohonan'));
        $this->output->set_header('Cache-Control: no-store, private');
        if ($this->input->is_ajax_request()) {
            return $this->json(array(
                'html' => $this->load->view('permohonan/results', $data, TRUE),
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
                'filters' => $listing['filters']
            ));
        }
        $this->render('permohonan/index', $data);
    }

    public function create()
    {
        if (!$this->verified_citizen()) {
            $this->session->set_flashdata('error', 'Akun Anda belum terverifikasi sebagai penduduk aktif kampung/desa ini. Permohonan baru belum dapat dibuat.');
            redirect('dashboard');
        }
        $this->load->model('Request_model');
        $this->render('permohonan/create', array('pageTitle' => 'Permohonan Baru', 'services' => $this->Request_model->service_types(isset($this->currentUser['village_id']) ? $this->currentUser['village_id'] : '')));
    }

    public function store()
    {
        $this->require_post();
        if (!$this->verified_citizen()) {
            $this->session->set_flashdata('error', 'Akun Anda belum terverifikasi sebagai penduduk aktif kampung/desa ini. Permohonan belum dapat dikirim.');
            redirect('dashboard');
        }
        $this->load->model('Request_model');
        $this->form_validation->set_rules('service_type', 'Jenis layanan', 'trim|required|max_length[80]');
        $this->form_validation->set_rules('purpose', 'Keperluan', 'trim|required|min_length[5]|max_length[500]');
        $this->form_validation->set_rules('note', 'Catatan', 'trim|max_length[1000]');
        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', trim(strip_tags(validation_errors())) ?: 'Form permohonan belum lengkap.');
            redirect('permohonan/baru');
        }
        $formFields = $this->input->post('warga_fields', FALSE);
        if (!is_array($formFields)) $formFields = array();
        $result = $this->Request_model->create($this->currentUser, array('service_type' => $this->input->post('service_type', TRUE), 'purpose' => $this->input->post('purpose', TRUE), 'note' => $this->input->post('note', TRUE), 'form_fields' => $formFields));
        if (empty($result['success'])) {
            $this->session->set_flashdata('error', isset($result['message']) ? $result['message'] : 'Permohonan belum dapat disimpan.');
            redirect('permohonan/baru');
        }
        $this->session->set_flashdata('success', 'Permohonan berhasil dikirim dan menunggu verifikasi desa.');
        redirect('permohonan/' . rawurlencode($result['id']));
    }

    public function show($id)
    {
        $this->load->model('Request_model');
        $request = $this->Request_model->find_for_user($id, $this->currentUser['id']);
        if (!$request) show_404();
        $request['documents'] = $this->Request_model->documents_for_user($id, $this->currentUser['id']);
        $this->render('permohonan/show', array('pageTitle' => 'Detail Permohonan', 'request' => $request, 'history' => $this->Request_model->history($id)));
    }

    public function document($id)
    {
        $this->load->model('Request_model');
        $document = $this->Request_model->official_document_for_user($id, $this->currentUser['id']);
        if (!$document || empty($document['document_path'])) show_404();
        if (!$this->stream_private_file($document['document_path'], 'surat-' . (string) $document['local_reference'], 'attachment', (string) $document['document_sha256'])) {
            show_404();
        }
    }

    private function verified_citizen()
    {
        return !empty($this->currentUser['id'])
            && $this->Auth_model->citizen_is_verified((int) $this->currentUser['id']);
    }
}
