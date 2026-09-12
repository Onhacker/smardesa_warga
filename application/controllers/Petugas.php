<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Petugas extends Staff_Controller
{
    public function index()
    {
        $this->load->model('Request_model');
        $listing = $this->Request_model->paginated_for_staff($this->currentUser, array(
            'status' => $this->input->get('status', TRUE),
            'page' => $this->input->get('page', TRUE)
        ));
        $institutionLabel = $this->institution_label();
        $data = array(
            'pageTitle' => 'Layanan ' . $institutionLabel,
            'institutionUpper' => function_exists('mb_strtoupper') ? mb_strtoupper($institutionLabel, 'UTF-8') : strtoupper($institutionLabel),
            'staffMode' => TRUE,
            'requests' => $listing['items'],
            'listing' => $listing,
            'listUrl' => site_url('petugas'),
            'summary' => $this->Request_model->staff_summary($this->currentUser),
            'selectedStatus' => $listing['filters']['status']
        );
        $this->output->set_header('Cache-Control: no-store, private');
        if ($this->input->is_ajax_request()) {
            return $this->json(array(
                'html' => $this->load->view('staff/results', $data, TRUE),
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
                'filters' => $listing['filters']
            ));
        }
        $this->render('staff/index', $data);
    }

    public function show($id)
    {
        $this->load->model('Request_model');
        $request = $this->Request_model->find_for_staff($id, $this->currentUser);
        if (!$request) show_404();
        $this->render('staff/show', array(
            'pageTitle' => 'Detail Permohonan',
            'staffMode' => TRUE,
            'request' => $request,
            'history' => $this->Request_model->history($id),
            'documents' => $this->Request_model->documents_for_staff($id, $this->currentUser),
            'actions' => $this->Request_model->allowed_actions($this->currentUser, $request)
        ));
    }

    public function action($id)
    {
        $this->require_post();
        $this->load->model('Request_model');
        $result = $this->Request_model->apply_action(
            $id,
            $this->currentUser,
            $this->input->post('action', TRUE),
            $this->input->post('note', TRUE)
        );
        $this->session->set_flashdata(!empty($result['success']) ? 'success' : 'error', $result['success'] ? 'Tindakan berhasil disimpan.' : (isset($result['message']) ? $result['message'] : 'Tindakan belum dapat disimpan.'));
        redirect('petugas/permohonan/' . rawurlencode($id));
    }

    public function document($id)
    {
        $this->load->model('Request_model');
        $document = $this->Request_model->document_for_staff($id, $this->currentUser);
        if (!$document || empty($document['storage_path'])) show_404();
        if (!$this->stream_private_file($document['storage_path'], $document['original_name'], 'attachment')) {
            show_404();
        }
    }
}
