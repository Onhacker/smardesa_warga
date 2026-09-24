<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verifikasi_surat extends Public_Controller
{
    public function index($id = '')
    {
        $this->output
            ->set_header('X-Robots-Tag: noindex, nofollow, noarchive')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private')
            ->set_header('Pragma: no-cache');

        $requestId = strtolower(trim(rawurldecode((string) $id)));
        $verification = NULL;
        if (preg_match('/^[a-f0-9-]{36}$/', $requestId)) {
            $this->load->model('Request_model');
            $verification = $this->Request_model->public_verification($requestId);
        }
        if (is_array($verification) && !empty($verification['regency_code'])) {
            $this->branding = $this->Branding_model->current($verification['regency_code']);
            $this->institutionLabel = '';
            $this->districtLabel = '';
        } else {
            $this->output->set_status_header(404);
        }

        $this->render('verification/letter', array(
            'pageTitle' => 'Verifikasi Surat',
            'verification' => $verification,
            'showBackButton' => TRUE,
            'backUrl' => base_url(),
            'shareTitle' => 'Verifikasi Surat | ' . ($this->branding['nama_sistem'] ?? 'Layanan Warga'),
            'shareDescription' => 'Pemeriksaan keaslian dokumen layanan warga.'
        ));
    }
}
