<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** JSON endpoints for Passkey/WebAuthn and six-digit PIN authentication. */
class Passkeys extends Public_Controller
{
    private function json_auth()
    {
        if ($this->currentUser && !empty($this->currentUser['id'])) return TRUE;
        $this->json(array('success' => FALSE, 'message' => 'Sesi login diperlukan.', 'login_url' => site_url('login')), 401);
        return FALSE;
    }

    private function model()
    {
        $this->load->model('Passkey_model');
        return $this->Passkey_model;
    }

    private function result_status(array $result)
    {
        return !empty($result['success']) ? 200 : 422;
    }

    public function status()
    {
        if (!$this->json_auth()) return;
        $result = $this->model()->status((int) $this->currentUser['id']);
        return $this->json(array('success' => TRUE, 'status' => $result));
    }

    public function register_options()
    {
        if (!$this->json_auth()) return;
        $this->require_post();
        $password = (string) $this->input->post('current_password');
        if (!$this->Auth_model->verify_password((int) $this->currentUser['id'], $password)) {
            return $this->json(array('success' => FALSE, 'message' => $this->Auth_model->error() ?: 'Kata sandi saat ini tidak sesuai.'), 422);
        }
        $result = $this->model()->registration_options($this->currentUser);
        return $this->json($result, $this->result_status($result));
    }

    public function register()
    {
        if (!$this->json_auth()) return;
        $this->require_post();
        $payload = $this->payload('credential');
        if ($payload === NULL) return $this->json(array('success' => FALSE, 'message' => 'Respons perangkat tidak lengkap.'), 422);
        $payload['label'] = (string) $this->input->post('label', TRUE);
        $result = $this->model()->verify_registration((int) $this->currentUser['id'], $payload);
        if (!empty($result['success'])) $this->Auth_model->authenticate_with_trusted_device($this->currentUser, 'passkey');
        return $this->json($result, $this->result_status($result));
    }

    public function revoke()
    {
        if (!$this->json_auth()) return;
        $this->require_post();
        $password = (string) $this->input->post('current_password');
        if (!$this->Auth_model->verify_password((int) $this->currentUser['id'], $password)) {
            return $this->json(array('success' => FALSE, 'message' => $this->Auth_model->error() ?: 'Kata sandi saat ini tidak sesuai.'), 422);
        }
        $id = (int) $this->input->post('id');
        $result = $this->model()->revoke((int) $this->currentUser['id'], $id);
        $this->Auth_model->revoke_trusted_devices((int) $this->currentUser['id'], 'passkey');
        return $this->json($result, $this->result_status($result));
    }

    public function pin_enable()
    {
        if (!$this->json_auth()) return;
        $this->require_post();
        $password = (string) $this->input->post('current_password');
        if (!$this->Auth_model->verify_password((int) $this->currentUser['id'], $password)) {
            return $this->json(array('success' => FALSE, 'message' => $this->Auth_model->error() ?: 'Kata sandi saat ini tidak sesuai.'), 422);
        }
        $pin = trim((string) $this->input->post('pin', TRUE));
        $confirm = trim((string) $this->input->post('pin_confirm', TRUE));
        if ($pin !== $confirm) return $this->json(array('success' => FALSE, 'message' => 'Konfirmasi PIN belum sama.'), 422);
        $result = $this->model()->set_pin((int) $this->currentUser['id'], $pin);
        if (!empty($result['success'])) {
            // A changed PIN must invalidate remembered sessions created with
            // the old PIN before this device receives its replacement token.
            $this->Auth_model->revoke_trusted_devices((int) $this->currentUser['id'], 'pin');
            $this->Auth_model->authenticate_with_trusted_device($this->currentUser, 'pin');
        }
        return $this->json($result, $this->result_status($result));
    }

    public function pin_disable()
    {
        if (!$this->json_auth()) return;
        $this->require_post();
        $password = (string) $this->input->post('current_password');
        if (!$this->Auth_model->verify_password((int) $this->currentUser['id'], $password)) {
            return $this->json(array('success' => FALSE, 'message' => $this->Auth_model->error() ?: 'Kata sandi saat ini tidak sesuai.'), 422);
        }
        $result = $this->model()->disable_pin((int) $this->currentUser['id']);
        $this->Auth_model->revoke_trusted_devices((int) $this->currentUser['id'], 'pin');
        return $this->json($result, $this->result_status($result));
    }

    public function login_options()
    {
        $this->require_post();
        $identity = trim((string) $this->input->post('identity', TRUE));
        if ($this->Auth_model->login_is_throttled($identity)) {
            return $this->json(array('success' => FALSE, 'message' => 'Terlalu banyak percobaan masuk. Silakan tunggu 15 menit.'), 429);
        }
        $result = $this->model()->login_options($identity);
        if (empty($result['success'])) $this->Auth_model->note_login_failure($identity);
        return $this->json($result, $this->result_status($result));
    }

    public function login()
    {
        $this->require_post();
        $payload = $this->payload('credential');
        if ($payload === NULL) return $this->json(array('success' => FALSE, 'message' => 'Respons perangkat tidak lengkap.'), 422);
        $stateIdentity = $this->session->userdata('warga_passkey_identity');
        $result = $this->model()->verify_login($payload);
        if (!empty($result['success']) && !empty($result['user'])) {
            if (!empty($result['identity'])) $this->Auth_model->clear_login_failure($result['identity']);
            $this->session->unset_userdata('warga_passkey_identity');
            $this->Auth_model->authenticate_with_trusted_device($result['user'], 'passkey');
            return $this->json(array('success' => TRUE, 'redirect' => warga_home_route($result['user']) === 'petugas' ? site_url('petugas') : site_url('dashboard'), 'message' => 'Login berhasil.'));
        }
        if (is_string($stateIdentity) && $stateIdentity !== '') $this->Auth_model->note_login_failure($stateIdentity);
        $this->session->unset_userdata('warga_passkey_identity');
        return $this->json($result, $this->result_status($result));
    }

    public function pin_login()
    {
        $this->require_post();
        $identity = trim((string) $this->input->post('identity', TRUE));
        $pin = trim((string) $this->input->post('pin', TRUE));
        if ($this->Auth_model->login_is_throttled($identity)) {
            return $this->json(array('success' => FALSE, 'message' => 'Terlalu banyak percobaan masuk. Silakan tunggu 15 menit.'), 429);
        }
        if (!preg_match('/^[0-9]{6}$/', $pin)) return $this->json(array('success' => FALSE, 'message' => 'PIN harus terdiri dari 6 angka.'), 422);
        $result = $this->model()->verify_pin($identity, $pin);
        if (!empty($result['success']) && !empty($result['user'])) {
            $this->Auth_model->clear_login_failure($identity);
            $this->Auth_model->authenticate_with_trusted_device($result['user'], 'pin');
            return $this->json(array('success' => TRUE, 'redirect' => warga_home_route($result['user']) === 'petugas' ? site_url('petugas') : site_url('dashboard'), 'message' => 'Login berhasil.'));
        }
        $this->Auth_model->note_login_failure($identity);
        return $this->json($result, $this->result_status($result));
    }

    private function payload($field)
    {
        $raw = $this->input->post($field);
        if (!is_string($raw) || strlen($raw) < 2 || strlen($raw) > 50000) return NULL;
        $payload = json_decode($raw, TRUE);
        return is_array($payload) ? $payload : NULL;
    }
}
