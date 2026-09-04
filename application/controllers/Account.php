<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends App_Controller
{
    public function index()
    {
        // The account page may contain identity data; never let an intermediary
        // or browser cache retain the rendered NIK/No. KK response.
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private')
            ->set_header('Pragma: no-cache');
        $this->render('account/index', array(
            'pageTitle' => 'Akun Saya',
            'staffMode' => warga_is_staff($this->currentUser),
            'accountProfile' => !warga_is_staff($this->currentUser)
                ? $this->Auth_model->citizen_profile_for_user((int) $this->currentUser['id'])
                : NULL
        ));
    }
}
