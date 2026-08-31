<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends App_Controller
{
    public function index()
    {
        $this->render('account/index', array('pageTitle' => 'Akun Saya', 'staffMode' => warga_is_staff($this->currentUser)));
    }
}
