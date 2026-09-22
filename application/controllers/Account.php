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

    /**
     * Keep sensitive login controls on their own no-store page.  Besides
     * making the account screen easier to scan, this prevents the Passkey
     * runtime from being loaded until the resident explicitly opens the
     * security settings.
     */
    public function security()
    {
        $this->no_store();
        $this->render('account/security', array(
            'pageTitle' => 'Biometrik & PIN',
            'showBackButton' => TRUE,
            'backUrl' => site_url('akun'),
            'loadPasskeyScript' => TRUE
        ));
    }

    public function edit()
    {
        $this->no_store();
        $sessionKey = 'warga_account_contact_change';
        $pending = $this->session->userdata($sessionKey);
        if (!is_array($pending) || (int) ($pending['user_id'] ?? 0) !== (int) $this->currentUser['id'] || (int) ($pending['expires_at'] ?? 0) <= time()) {
            $this->session->unset_userdata($sessionKey);
            $pending = array();
        }
        $data = array(
            'pageTitle' => 'Edit Akun', 'showBackButton' => TRUE, 'backUrl' => site_url('akun'),
            'formValues' => array('email' => (string) $this->currentUser['email'], 'phone' => (string) $this->currentUser['phone']),
            'fieldErrors' => array(), 'demoMode' => warga_demo_mode(), 'pendingChange' => $pending
        );
        if ($this->input->method(TRUE) === 'POST') {
            if ($this->input->post('cancel_change') !== NULL) {
                $this->session->unset_userdata($sessionKey);
                redirect('akun/edit');
            }
            if ($pending && !warga_demo_mode()) {
                $otp = preg_replace('/\D+/', '', (string) $this->input->post('otp', TRUE));
                if (!preg_match('/^[0-9]{6}$/', $otp)) {
                    $data['fieldErrors']['otp'] = 'Masukkan kode 6 digit dari email.';
                } else {
                    $result = $this->Auth_model->complete_account_change((int) $this->currentUser['id'], 'contact', $pending['request_token'], $otp, $pending['email'], $pending['phone']);
                    $expectedEmail = strtolower(trim((string) $pending['email']));
                    $expectedPhone = preg_replace('/[^0-9+]/', '', (string) $pending['phone']);
                    $savedEmail = strtolower(trim((string) ($result['email'] ?? '')));
                    $savedPhone = preg_replace('/[^0-9+]/', '', (string) ($result['phone'] ?? ''));
                    if (!empty($result['success']) && $savedEmail === $expectedEmail && $savedPhone === $expectedPhone) {
                        $this->session->unset_userdata($sessionKey);
                        $this->redirect_with('akun', 'success', 'Email dan nomor telepon berhasil diperbarui. Gunakan email baru untuk lupa kata sandi.');
                    }
                    if (!empty($result['success'])) {
                        $this->session->unset_userdata($sessionKey);
                        $data['fieldErrors']['otp'] = 'Data sudah diproses tetapi hasil verifikasi kontak tidak sesuai. Muat ulang halaman akun sebelum mencoba lagi.';
                    } else {
                        $data['fieldErrors']['otp'] = $result['message'] ?? 'Perubahan akun belum berhasil.';
                    }
                }
                $data['formValues'] = array('email' => $pending['email'], 'phone' => $pending['phone']);
                $this->render('account/edit', $data);
                return;
            }
            $emailInput = $this->input->post('email', TRUE);
            $phoneInput = $this->input->post('phone', TRUE);
            $currentInput = $this->input->post('current_password');
            $data['formValues'] = array('email' => is_string($emailInput) ? strtolower(trim($emailInput)) : '', 'phone' => is_string($phoneInput) ? trim($phoneInput) : '');
            $current = is_string($currentInput) ? $currentInput : '';
            if ($data['formValues']['email'] === '') {
                $data['fieldErrors']['contact'] = 'Email aktif wajib diisi agar keamanan dan pemulihan akun tetap tersedia.';
            } elseif (strlen($data['formValues']['email']) > 160 || !filter_var($data['formValues']['email'], FILTER_VALIDATE_EMAIL)) {
                $data['fieldErrors']['contact'] = 'Masukkan alamat email yang valid.';
            }
            if (!$current) $data['fieldErrors']['current_password'] = 'Masukkan kata sandi saat ini.';
            if (!$data['fieldErrors'] && !$this->Auth_model->verify_password((int) $this->currentUser['id'], $current)) $data['fieldErrors']['current_password'] = $this->Auth_model->error() ?: 'Kata sandi saat ini tidak sesuai.';
            if (!$data['fieldErrors']) {
                if (warga_demo_mode()) {
                    $result = $this->Auth_model->update_contact((int) $this->currentUser['id'], $data['formValues']['email'], $data['formValues']['phone']);
                    if ($result['success']) $this->redirect_with('akun', 'success', 'Data akun berhasil diperbarui.');
                } else {
                    $result = $this->Auth_model->request_account_change((int) $this->currentUser['id'], $current, 'contact', $data['formValues']['email'], $data['formValues']['phone']);
                    if (!empty($result['success']) && !empty($result['request_token'])) {
                        $pending = array('user_id' => (int) $this->currentUser['id'], 'request_token' => (string) $result['request_token'],
                            'email' => $data['formValues']['email'], 'phone' => $data['formValues']['phone'],
                            'email_masked' => (string) ($result['email_masked'] ?? ''), 'expires_at' => time() + max(60, (int) ($result['expires_in'] ?? 600)));
                        $this->session->set_userdata($sessionKey, $pending);
                        $data['pendingChange'] = $pending;
                    }
                }
                if (empty($result['success'])) $data['fieldErrors']['contact'] = $result['message'] ?? 'Perubahan akun belum dapat dimulai.';
            }
        }
        $this->render('account/edit', $data);
    }

    public function password()
    {
        $this->no_store();
        $sessionKey = 'warga_account_password_change';
        $pending = $this->session->userdata($sessionKey);
        if (!is_array($pending) || (int) ($pending['user_id'] ?? 0) !== (int) $this->currentUser['id'] || (int) ($pending['expires_at'] ?? 0) <= time()) {
            $this->session->unset_userdata($sessionKey);
            $pending = array();
        }
        $data = array('pageTitle' => 'Ganti Password', 'showBackButton' => TRUE, 'backUrl' => site_url('akun'), 'fieldErrors' => array(), 'demoMode' => warga_demo_mode(), 'pendingChange' => $pending);
        if ($this->input->method(TRUE) === 'POST') {
            if ($this->input->post('cancel_change') !== NULL) {
                $this->session->unset_userdata($sessionKey);
                redirect('akun/ganti-password');
            }
            $newInput = $this->input->post('new_password');
            $confirmInput = $this->input->post('password_confirm');
            $currentInput = $this->input->post('current_password');
            $current = is_string($currentInput) ? $currentInput : '';
            $new = is_string($newInput) ? $newInput : '';
            $confirm = is_string($confirmInput) ? $confirmInput : '';
            if (!$pending || warga_demo_mode()) {
                if (!$current) $data['fieldErrors']['current_password'] = 'Masukkan kata sandi saat ini.';
                if (warga_demo_mode()) {
                    if (strlen($new) < 8 || strlen($new) > 72 || strpos($new, "\0") !== FALSE) $data['fieldErrors']['new_password'] = 'Kata sandi baru harus 8–72 karakter tanpa karakter kosong.';
                    if ($new !== $confirm) $data['fieldErrors']['password_confirm'] = 'Konfirmasi kata sandi belum sama.';
                }
                if (!$data['fieldErrors']) {
                    $result = warga_demo_mode()
                        ? $this->Auth_model->change_password((int) $this->currentUser['id'], $current, $new)
                        : $this->Auth_model->request_account_change((int) $this->currentUser['id'], $current, 'password', (string) $this->currentUser['email'], (string) $this->currentUser['phone']);
                    if (!empty($result['success'])) {
                        if (warga_demo_mode()) $this->redirect_with('akun', 'success', 'Kata sandi berhasil diubah.');
                        $pending = array('user_id' => (int) $this->currentUser['id'], 'request_token' => (string) $result['request_token'],
                            'email' => strtolower(trim((string) $this->currentUser['email'])), 'phone' => trim((string) $this->currentUser['phone']),
                            'email_masked' => (string) ($result['email_masked'] ?? ''), 'expires_at' => time() + max(60, (int) ($result['expires_in'] ?? 600)));
                        $this->session->set_userdata($sessionKey, $pending);
                        $data['pendingChange'] = $pending;
                    } else $data['fieldErrors']['current_password'] = $result['message'] ?? 'Kode belum dapat dikirim.';
                }
            } else {
                $otp = preg_replace('/\D+/', '', (string) $this->input->post('otp', TRUE));
                if (!preg_match('/^[0-9]{6}$/', $otp)) $data['fieldErrors']['otp'] = 'Masukkan kode 6 digit dari email.';
                if (strlen($new) < 8 || strlen($new) > 72 || strpos($new, "\0") !== FALSE) $data['fieldErrors']['new_password'] = 'Kata sandi baru harus 8–72 karakter tanpa karakter kosong.';
                if ($new !== $confirm) $data['fieldErrors']['password_confirm'] = 'Konfirmasi kata sandi belum sama.';
                if (!$data['fieldErrors']) {
                    $result = $this->Auth_model->complete_account_change((int) $this->currentUser['id'], 'password', $pending['request_token'], $otp, $pending['email'], $pending['phone'], $new);
                    if (!empty($result['success'])) {
                        $this->session->unset_userdata($sessionKey);
                        $this->Auth_model->logout();
                        $this->redirect_with('login', 'success', 'Kata sandi berhasil diperbarui. Silakan masuk kembali.');
                    }
                    $data['fieldErrors']['otp'] = $result['message'] ?? 'Kata sandi belum dapat diubah.';
                }
            }
        }
        $this->render('account/password', $data);
    }
}
