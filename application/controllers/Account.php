<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends App_Controller
{
    private function no_store()
    {
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private')
            ->set_header('Pragma: no-cache');
    }

    public function index()
    {
        // The account page may contain identity data; never let an intermediary
        // or browser cache retain the rendered NIK/No. KK response.
        $this->no_store();
        $this->render('account/index', array(
            'pageTitle' => 'Akun Saya',
            'staffMode' => warga_is_staff($this->currentUser),
            'accountProfile' => !warga_is_staff($this->currentUser)
                ? $this->Auth_model->citizen_profile_for_user((int) $this->currentUser['id'])
                : NULL
        ));
    }

    public function edit()
    {
        $this->no_store();
        $data = array(
            'pageTitle' => 'Edit Akun', 'showBackButton' => TRUE, 'backUrl' => site_url('akun'),
            'formValues' => array('email' => (string) $this->currentUser['email'], 'phone' => (string) $this->currentUser['phone']),
            'fieldErrors' => array(), 'demoMode' => warga_demo_mode()
        );
        if ($this->input->method(TRUE) === 'POST') {
            $data['formValues'] = array('email' => trim((string) $this->input->post('email', TRUE)), 'phone' => trim((string) $this->input->post('phone', TRUE)));
            $current = (string) $this->input->post('current_password');
            if (!$data['formValues']['email'] && !$data['formValues']['phone']) $data['fieldErrors']['contact'] = 'Isi minimal email atau nomor telepon.';
            if (!$current) $data['fieldErrors']['current_password'] = 'Masukkan kata sandi saat ini.';
            if (!$data['fieldErrors'] && !$this->Auth_model->verify_password((int) $this->currentUser['id'], $current)) $data['fieldErrors']['current_password'] = 'Kata sandi saat ini tidak sesuai.';
            if (!$data['fieldErrors']) {
                $result = $this->Auth_model->update_contact((int) $this->currentUser['id'], $data['formValues']['email'], $data['formValues']['phone']);
                if ($result['success']) $this->redirect_with('akun', 'success', 'Data akun berhasil diperbarui.');
                $data['fieldErrors']['contact'] = $result['message'];
            }
        }
        $this->render('account/edit', $data);
    }

    public function password()
    {
        $this->no_store();
        $data = array('pageTitle' => 'Ganti Password', 'showBackButton' => TRUE, 'backUrl' => site_url('akun'), 'fieldErrors' => array(), 'demoMode' => warga_demo_mode());
        if ($this->input->method(TRUE) === 'POST') {
            $current = (string) $this->input->post('current_password');
            $new = (string) $this->input->post('new_password');
            $confirm = (string) $this->input->post('password_confirm');
            if (!$current) $data['fieldErrors']['current_password'] = 'Masukkan kata sandi saat ini.';
            if (strlen($new) < 8 || strlen($new) > 72) $data['fieldErrors']['new_password'] = 'Kata sandi baru harus 8–72 karakter.';
            if ($new !== $confirm) $data['fieldErrors']['password_confirm'] = 'Konfirmasi kata sandi belum sama.';
            if (!$data['fieldErrors']) {
                $result = $this->Auth_model->change_password((int) $this->currentUser['id'], $current, $new);
                if ($result['success']) $this->redirect_with('akun', 'success', 'Kata sandi berhasil diubah.');
                $data['fieldErrors']['current_password'] = $result['message'];
            }
        }
        $this->render('account/password', $data);
    }
}
