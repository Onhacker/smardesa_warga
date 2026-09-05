<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Community extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Community_model', 'community');
        $this->output->set_header('Cache-Control: no-store, private');
    }

    public function announcements()
    {
        $this->render('community/announcements', array('pageTitle' => 'Pengumuman',
            'items' => $this->community->announcements($this->currentUser),
            'canManage' => $this->community->can_manage($this->currentUser), 'ready' => $this->community->ready()));
    }

    public function announcement($id)
    {
        $item = $this->community->announcement($id, $this->currentUser);
        if (!$item) show_404();
        $this->render('community/announcement', array('pageTitle' => 'Pengumuman', 'item' => $item,
            'canManage' => $this->community->can_manage($this->currentUser), 'showBackButton' => true, 'backUrl' => site_url('pengumuman')));
    }

    public function publish()
    {
        $this->require_post();
        if (!$this->community->can_manage($this->currentUser)) show_error('Akses ditolak.', 403);
        $this->form_validation->set_rules('title', 'Judul', 'trim|required|max_length[180]');
        $this->form_validation->set_rules('body', 'Isi pengumuman', 'trim|required|min_length[10]|max_length[10000]');
        if (!$this->form_validation->run()) $this->redirect_with('pengumuman', 'error', trim(strip_tags(validation_errors())));
        $id = $this->community->publish($this->currentUser, trim((string)$this->input->post('title')), trim((string)$this->input->post('body')));
        $this->redirect_with($id ? 'pengumuman/'.$id : 'pengumuman', $id ? 'success' : 'error', $id ? 'Pengumuman diterbitkan.' : 'Pengumuman belum dapat disimpan.');
    }

    public function archive($id)
    {
        $this->require_post();
        if (!$this->community->can_manage($this->currentUser)) show_error('Akses ditolak.', 403);
        $ok = $this->community->archive_announcement($id, $this->currentUser);
        $this->redirect_with('pengumuman', $ok ? 'success' : 'error', $ok ? 'Pengumuman diarsipkan.' : 'Pengumuman tidak ditemukan.');
    }

    public function complaints()
    {
        $this->render('community/complaints', array('pageTitle' => 'Pengaduan', 'items' => $this->community->complaints($this->currentUser),
            'canManage' => $this->community->can_manage($this->currentUser), 'ready' => $this->community->ready()));
    }

    public function submit()
    {
        $this->require_post();
        if (($this->currentUser['role_slug'] ?? '') !== 'warga') show_error('Akses ditolak.', 403);
        $this->form_validation->set_rules('title', 'Judul', 'trim|required|max_length[180]');
        $this->form_validation->set_rules('body', 'Isi pengaduan', 'trim|required|min_length[10]|max_length[5000]');
        $this->form_validation->set_rules('location', 'Lokasi', 'trim|max_length[255]');
        if (!$this->form_validation->run()) $this->redirect_with('pengaduan', 'error', trim(strip_tags(validation_errors())));
        $id = $this->community->submit_complaint($this->currentUser, trim((string)$this->input->post('title')),
            trim((string)$this->input->post('body')), trim((string)$this->input->post('location')));
        $this->redirect_with($id ? 'pengaduan/'.$id : 'pengaduan', $id ? 'success' : 'error',
            $id ? 'Pengaduan dikirim kepada Kepala Desa dan Sekdes.' : 'Pengaduan belum dapat dikirim. Pastikan akun terverifikasi dan batas 10 pengaduan per hari belum tercapai.');
    }

    public function complaint($id)
    {
        $item = $this->community->complaint($id, $this->currentUser);
        if (!$item) show_404();
        $this->render('community/complaint', array('pageTitle' => 'Detail Pengaduan', 'item' => $item,
            'replies' => $this->community->replies($id), 'canManage' => $this->community->can_manage($this->currentUser),
            'showBackButton' => true, 'backUrl' => site_url('pengaduan')));
    }

    public function reply($id)
    {
        $this->require_post();
        if (!$this->community->can_manage($this->currentUser)) show_error('Akses ditolak.', 403);
        $this->form_validation->set_rules('message', 'Tanggapan', 'trim|required|min_length[5]|max_length[3000]');
        if (!$this->form_validation->run()) $this->redirect_with('pengaduan/'.$id, 'error', trim(strip_tags(validation_errors())));
        $ok = $this->community->reply($id, $this->currentUser, trim((string)$this->input->post('message')), $this->input->post('status'));
        $this->redirect_with('pengaduan/'.$id, $ok ? 'success' : 'error', $ok ? 'Tanggapan dikirim.' : 'Tanggapan belum dapat disimpan.');
    }

    public function contact()
    {
        $village = $this->community->village($this->currentUser['village_id'], $this->currentUser['village_name'] ?? '');
        $this->render('community/contact', array('pageTitle' => 'Kontak '.$village['institution'], 'village' => $village));
    }

    public function privacy()
    {
        $village = $this->community->village($this->currentUser['village_id'], $this->currentUser['village_name'] ?? '');
        $this->render('community/privacy', array(
            'pageTitle' => 'Kebijakan Privasi',
            'village' => $village,
            'showBackButton' => TRUE,
            'backUrl' => site_url('akun')
        ));
    }

    public function terms()
    {
        $village = $this->community->village($this->currentUser['village_id'], $this->currentUser['village_name'] ?? '');
        $this->render('community/terms', array(
            'pageTitle' => 'Syarat & Ketentuan',
            'village' => $village,
            'showBackButton' => TRUE,
            'backUrl' => site_url('akun')
        ));
    }
}
