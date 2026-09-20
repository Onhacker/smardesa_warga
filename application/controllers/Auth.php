<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Public_Controller
{
    public function login()
    {
        if ($this->currentUser) redirect('dashboard');
        $publicInstitution = trim((string) (getenv('PUBLIC_INSTITUTION_LABEL') ?: 'Kampung')) ?: 'Kampung';
        $publicArea = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
        // The login screen is rendered outside the authenticated app layout,
        // but it still uses the same public tenant identity as the shared
        // footer.  Keep that context available so the footer is branded
        // consistently before a session exists.
        $data = array(
            'pageTitle' => 'Masuk | SI DAPULIK',
            'demoMode' => warga_demo_mode(),
            'currentUser' => NULL,
            'isAuthenticated' => FALSE,
            'staffMode' => FALSE,
            'institutionLabel' => $publicInstitution,
            'footerVillage' => array(
                'name' => $publicArea,
                'institution' => $publicInstitution,
                'contact' => array()
            )
        );
        if ($this->input->method(TRUE) === 'POST') {
            $this->form_validation->set_rules('identity', 'Email atau nomor telepon', 'trim|required|max_length[160]');
            $this->form_validation->set_rules('password', 'Kata sandi', 'required|max_length[200]');
            if ($this->form_validation->run()) {
                $user = $this->Auth_model->attempt($this->input->post('identity', TRUE), (string) $this->input->post('password'));
                if ($user) {
                    $intended = $this->session->userdata('intended_url');
                    $this->session->unset_userdata('intended_url');
                    // Older sessions may still contain the notification
                    // summary endpoint from before it returned a JSON 401.
                    // Never navigate a normal browser to that API response
                    // after login; fall back to the user's regular home page.
                    $intendedPath = parse_url((string) $intended, PHP_URL_PATH);
                    if (is_string($intendedPath)
                        && preg_match('#(?:^|/)notifikasi/ringkasan/?$#', trim($intendedPath, '/'))) {
                        $intended = NULL;
                    }
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
            'pageTitle' => 'Daftar Akun | SI DAPULIK',
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

    public function forgot_password()
    {
        if ($this->currentUser) redirect('dashboard');
        $expiredMessage = '';
        $state = $this->session->userdata('warga_password_reset');
        if (!is_array($state) || empty($state['request_token']) || empty($state['expires_at'])) {
            $state = array();
        } elseif ((int) $state['expires_at'] <= time()) {
            $this->session->unset_userdata('warga_password_reset');
            $state = array();
            $expiredMessage = 'Kode sebelumnya sudah kedaluwarsa. Minta kode baru.';
        }

        $data = $this->public_auth_data('Lupa Kata Sandi | SI DAPULIK');
        $data['resetState'] = $state;
        $data['error'] = $this->session->flashdata('reset_error') ?: $expiredMessage;
        $data['notice'] = $this->session->flashdata('reset_notice');
        $this->load->view('auth/forgot_password', $data);
    }

    public function forgot_password_request()
    {
        $this->require_post();
        if ($this->currentUser) redirect('dashboard');
        if (warga_demo_mode()) {
            $this->session->set_flashdata('reset_error', 'Reset password email tidak dijalankan pada mode demo.');
            redirect('lupa-password');
        }

        $email = strtolower(trim((string) $this->input->post('email', TRUE)));
        if (strlen($email) > 180 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('reset_error', 'Masukkan alamat email yang valid.');
            redirect('lupa-password');
        }
        $result = $this->Auth_model->request_password_reset($email);
        if (empty($result['success']) || empty($result['request_token'])) {
            $this->session->set_flashdata('reset_error', isset($result['message']) ? $result['message'] : 'Kode belum dapat diminta.');
            redirect('lupa-password');
        }

        $this->session->set_userdata('warga_password_reset', array(
            'request_token' => (string) $result['request_token'],
            'email' => $email,
            'email_masked' => isset($result['email_masked']) ? (string) $result['email_masked'] : $email,
            'expires_at' => time() + max(60, (int) (isset($result['expires_in']) ? $result['expires_in'] : 600)),
            'resend_at' => time() + max(30, (int) (isset($result['resend_after']) ? $result['resend_after'] : 60))
        ));
        $this->session->set_flashdata('reset_notice', isset($result['message']) ? $result['message'] : 'Jika email terdaftar, kode telah dikirim.');
        redirect('lupa-password');
    }

    public function forgot_password_resend()
    {
        $this->require_post();
        if ($this->currentUser) redirect('dashboard');
        if (warga_demo_mode()) {
            $this->session->set_flashdata('reset_error', 'Reset password email tidak dijalankan pada mode demo.');
            redirect('lupa-password');
        }
        $state = $this->session->userdata('warga_password_reset');
        if (!is_array($state) || empty($state['email'])) {
            $this->session->set_flashdata('reset_error', 'Mulai kembali dengan memasukkan email akun.');
            redirect('lupa-password');
        }
        if (!empty($state['resend_at']) && (int) $state['resend_at'] > time()) {
            $this->session->set_flashdata('reset_error', 'Kode baru dapat diminta setelah 60 detik.');
            redirect('lupa-password');
        }

        $result = $this->Auth_model->request_password_reset((string) $state['email']);
        if (empty($result['success']) || empty($result['request_token'])) {
            $this->session->set_flashdata('reset_error', isset($result['message']) ? $result['message'] : 'Kode baru belum dapat dikirim.');
            redirect('lupa-password');
        }
        $state['request_token'] = (string) $result['request_token'];
        $state['email_masked'] = isset($result['email_masked']) ? (string) $result['email_masked'] : (string) $state['email_masked'];
        $state['expires_at'] = time() + max(60, (int) (isset($result['expires_in']) ? $result['expires_in'] : 600));
        $state['resend_at'] = time() + max(30, (int) (isset($result['resend_after']) ? $result['resend_after'] : 60));
        $this->session->set_userdata('warga_password_reset', $state);
        $this->session->set_flashdata('reset_notice', 'Jika email terdaftar, kode baru telah dikirim.');
        redirect('lupa-password');
    }

    public function forgot_password_complete()
    {
        $this->require_post();
        if ($this->currentUser) redirect('dashboard');
        $state = $this->session->userdata('warga_password_reset');
        if (!is_array($state) || empty($state['request_token']) || empty($state['expires_at']) || (int) $state['expires_at'] <= time()) {
            $this->session->unset_userdata('warga_password_reset');
            $this->session->set_flashdata('reset_error', 'Kode sudah kedaluwarsa. Minta kode baru.');
            redirect('lupa-password');
        }

        $otp = preg_replace('/\D+/', '', (string) $this->input->post('otp', TRUE));
        $password = (string) $this->input->post('new_password');
        $confirmation = (string) $this->input->post('password_confirm');
        if (!preg_match('/^[0-9]{6}$/', $otp)) {
            $this->session->set_flashdata('reset_error', 'Masukkan kode 6 digit dari email.');
            redirect('lupa-password');
        }
        if (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== FALSE) {
            $this->session->set_flashdata('reset_error', 'Kata sandi baru harus 8–72 karakter.');
            redirect('lupa-password');
        }
        if (!hash_equals($password, $confirmation)) {
            $this->session->set_flashdata('reset_error', 'Konfirmasi kata sandi belum sama.');
            redirect('lupa-password');
        }

        $result = $this->Auth_model->complete_password_reset((string) $state['request_token'], $otp, $password);
        if (empty($result['success'])) {
            $this->session->set_flashdata('reset_error', isset($result['message']) ? $result['message'] : 'Reset password belum berhasil.');
            redirect('lupa-password');
        }
        $this->session->unset_userdata('warga_password_reset');
        $this->session->set_flashdata('success_title', 'Kata sandi diperbarui');
        $this->session->set_flashdata('success', isset($result['message']) ? $result['message'] : 'Kata sandi berhasil diperbarui.');
        redirect('login');
    }

    public function forgot_password_restart()
    {
        $this->require_post();
        $this->session->unset_userdata('warga_password_reset');
        redirect('lupa-password');
    }

    public function logout()
    {
        $this->require_post();
        $this->Auth_model->logout();
        redirect('login');
    }

    private function public_auth_data($page_title)
    {
        $institution = trim((string) (getenv('PUBLIC_INSTITUTION_LABEL') ?: 'Kampung')) ?: 'Kampung';
        $area = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
        return array(
            'pageTitle' => $page_title,
            'demoMode' => warga_demo_mode(),
            'currentUser' => NULL,
            'isAuthenticated' => FALSE,
            'staffMode' => FALSE,
            'institutionLabel' => $institution,
            'footerVillage' => array('name' => $area, 'institution' => $institution, 'contact' => array())
        );
    }
}
