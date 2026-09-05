<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Public_Controller
{
    public function login()
    {
        if ($this->currentUser) redirect('dashboard');
        $data = array('pageTitle' => 'Masuk | SmartDesa Warga', 'demoMode' => warga_demo_mode());
        if ($this->input->method(TRUE) === 'POST') {
            $this->form_validation->set_rules('identity', 'Email atau nomor telepon', 'trim|required|max_length[160]');
            $this->form_validation->set_rules('password', 'Kata sandi', 'required|max_length[200]');
            if ($this->form_validation->run()) {
                $user = $this->Auth_model->attempt($this->input->post('identity', TRUE), (string) $this->input->post('password'));
                if ($user) {
                    $intended = $this->session->userdata('intended_url');
                    $this->session->unset_userdata('intended_url');
                    redirect($intended ?: warga_home_route($user));
                }
                $data['error'] = $this->Auth_model->error() ?: 'Email/nomor telepon atau kata sandi tidak sesuai.';
            }
        }
        $this->load->view('auth/login', $data);
    }

    public function register()
    {
        if ($this->currentUser) redirect('dashboard');
        $data = array(
            'pageTitle' => 'Daftar Akun | SmartDesa Warga',
            'demoMode' => warga_demo_mode(),
            'registrationRegions' => $this->Auth_model->registration_regions()
        );
        if ($this->input->method(TRUE) === 'POST') {
            $this->form_validation->set_rules('name', 'Nama lengkap', 'trim|required|min_length[3]|max_length[120]');
            $this->form_validation->set_rules('nik', 'NIK', 'trim|required|max_length[25]');
            $this->form_validation->set_rules('kk', 'No. KK', 'trim|required|max_length[25]');
            $this->form_validation->set_rules('contact', 'Email atau nomor telepon', 'trim|required|max_length[160]');
            $this->form_validation->set_rules('district_code', 'Distrik/Kecamatan', 'trim|required|max_length[20]');
            $this->form_validation->set_rules('village_code', 'Wilayah', 'trim|required|max_length[30]');
            $this->form_validation->set_rules('password', 'Kata sandi', 'required|min_length[8]|max_length[200]');
            $this->form_validation->set_rules('password_confirm', 'Konfirmasi kata sandi', 'required|matches[password]');
            if ($this->form_validation->run()) {
                $result = $this->Auth_model->register_citizen(array(
                    'name' => $this->input->post('name', TRUE),
                    'nik' => $this->input->post('nik', TRUE),
                    'kk' => $this->input->post('kk', TRUE),
                    'contact' => $this->input->post('contact', TRUE),
                    'district_code' => $this->input->post('district_code', TRUE),
                    'village_code' => $this->input->post('village_code', TRUE),
                    'password' => (string) $this->input->post('password')
                ));
                if (!empty($result['success'])) {
                    $this->session->set_flashdata('success', 'Silakan masuk menggunakan akun yang baru Anda buat.');
                    redirect('login');
                }
                $data['error'] = isset($result['message']) ? $result['message'] : 'Pendaftaran belum dapat diproses.';
            }
        }
        $this->load->view('auth/register', $data);
    }

    public function logout()
    {
        $this->require_post();
        $this->Auth_model->logout();
        redirect('login');
    }
}
