<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends Public_Controller
{
    public function health()
    {
        $allowed = trim((string) getenv('WARGA_ALLOWED_ORIGIN'));
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
        if ($allowed !== '' && $origin !== '' && hash_equals($allowed, $origin)) {
            $this->output->set_header('Access-Control-Allow-Origin: ' . $origin);
            $this->output->set_header('Vary: Origin');
        }
        if (strtoupper($this->input->method(TRUE)) === 'OPTIONS') {
            $this->output->set_status_header(204)->set_header('Access-Control-Allow-Methods: GET, POST, OPTIONS')->set_header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-TOKEN');
            return;
        }
        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(array('success' => TRUE, 'service' => 'SmartDesa Warga API', 'status' => 'ready', 'time' => date('c')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
