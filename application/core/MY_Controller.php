<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $currentUser = NULL;
    protected $institutionLabel = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Auth_model');
        $this->currentUser = $this->Auth_model->current_user();
    }

    protected function institution_label()
    {
        if ($this->institutionLabel !== '') return $this->institutionLabel;
        if (!$this->currentUser) {
            $this->institutionLabel = trim((string) (getenv('PUBLIC_INSTITUTION_LABEL') ?: 'Kampung')) ?: 'Kampung';
            return $this->institutionLabel;
        }
        $this->load->model('Community_model');
        $village = $this->Community_model->village(
            $this->currentUser['village_id'] ?? '',
            $this->currentUser['village_name'] ?? ''
        );
        $this->institutionLabel = trim((string) ($village['institution'] ?? 'Desa')) ?: 'Desa';
        return $this->institutionLabel;
    }

    protected function institution_label_lower()
    {
        $label = $this->institution_label();
        return function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
    }

    protected function render($view, array $data = array())
    {
        $data['currentUser'] = $this->currentUser;
        $data['isAuthenticated'] = is_array($this->currentUser) && !empty($this->currentUser['id']);
        $this->load->model('Community_model');
        if ($data['isAuthenticated']) {
            $contactVillage = $this->Community_model->village($this->currentUser['village_id'] ?? '', $this->currentUser['village_name'] ?? '');
        } else {
            $contactVillage = array(
                'name' => trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya',
                'institution' => $this->institution_label(),
                'contact' => array()
            );
        }
        $data['institutionLabel'] = $this->institution_label();
        $data['institutionLower'] = function_exists('mb_strtolower')
            ? mb_strtolower($data['institutionLabel'], 'UTF-8')
            : strtolower($data['institutionLabel']);
        $data['institutionUpper'] = function_exists('mb_strtoupper')
            ? mb_strtoupper($data['institutionLabel'], 'UTF-8')
            : strtoupper($data['institutionLabel']);
        // Keep the complete tenant/contact context available to shared layout
        // components.  The footer uses this data for the identity and contact
        // buttons, while the page views remain responsible for their own data.
        $data['footerVillage'] = $contactVillage;
        $data['pageTitle'] = isset($data['pageTitle']) ? $data['pageTitle'] : 'SmartDesa Warga';
        $data['staffMode'] = isset($data['staffMode']) ? (bool) $data['staffMode'] : warga_is_staff($this->currentUser);
        $data['canManageMarketplace'] = FALSE;
        if ($data['isAuthenticated']) {
            $this->load->model('Marketplace_model', 'renderMarketplace');
            $data['canManageMarketplace'] = $this->renderMarketplace->can_manage($this->currentUser);
        }
        // The compact AppKit header is navigation-only on primary screens.
        // Reserve a back action for secondary flows and specific records.
        $data['showBackButton'] = array_key_exists('showBackButton', $data)
            ? (bool) $data['showBackButton']
            : in_array($view, array('permohonan/create', 'permohonan/show', 'staff/show'), TRUE);
        if (!isset($data['backUrl'])) {
            $data['backUrl'] = $view === 'staff/show' ? site_url('petugas') : site_url('permohonan');
        }
        $data['contentView'] = $view;
        $this->load->view('layouts/app', $data);
    }

    protected function json($payload, $status = 200)
    {
        $payload['csrf'] = array('name' => $this->security->get_csrf_token_name(), 'hash' => $this->security->get_csrf_hash());
        return $this->output->set_status_header($status)->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function require_post()
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') show_error('Metode permintaan tidak diizinkan.', 405);
    }

    /**
     * Guard actions that contain resident data or change application state.
     * Public pages call this guard only for their protected sub-routes so the
     * requested URL is still restored after a successful login.
     */
    protected function require_authentication()
    {
        if ($this->currentUser) return;
        $this->session->set_userdata('intended_url', current_url());
        redirect('login');
    }

    protected function redirect_with($url, $type, $message)
    {
        $this->session->set_flashdata($type, $message);
        redirect($url);
    }

    /**
     * Sajikan berkas yang berada di penyimpanan privat setelah memeriksa
     * traversal path, ukuran, dan MIME aktual. Browser tidak pernah menerima
     * lokasi fisik berkas.
     */
    protected function stream_private_file($path, $originalName = '', $disposition = 'attachment', $expectedSha256 = '')
    {
        $configured = trim((string) getenv('PRIVATE_STORAGE_PATH'));
        if (ENVIRONMENT === 'production' && $configured === '') {
            return FALSE;
        }
        $root = realpath($configured !== '' ? $configured : FCPATH . 'storage');
        $real = realpath((string) $path);
        if ($root === FALSE || !is_dir($root) || !is_readable($root)
            || $real === FALSE || !is_file($real) || !is_readable($real)) {
            return FALSE;
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($real !== $root && strpos($real, $rootPrefix) !== 0) {
            return FALSE;
        }

        $size = @filesize($real);
        if ($size === FALSE || $size < 1 || $size > 8 * 1024 * 1024) {
            return FALSE;
        }

        $mime = $this->private_file_mime($real);
        $extensions = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        );
        if (!isset($extensions[$mime])) {
            return FALSE;
        }

        $body = @file_get_contents($real);
        if (!is_string($body) || $body === '') {
            return FALSE;
        }
        if ($expectedSha256 !== '' && (!preg_match('/^[a-f0-9]{64}$/', (string) $expectedSha256)
            || !hash_equals((string) $expectedSha256, hash('sha256', $body)))) {
            return FALSE;
        }

        $name = $this->private_file_name($originalName, $extensions[$mime], $real);
        $disposition = in_array($disposition, array('inline', 'attachment'), TRUE)
            ? $disposition : 'attachment';
        $this->output
            ->set_status_header(200)
            ->set_content_type($mime)
            ->set_header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"')
            ->set_header('Content-Length: ' . (int) $size)
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private')
            ->set_header('Pragma: no-cache')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output($body);
        return TRUE;
    }

    protected function stream_private_html($path, $originalName = '', $disposition = 'inline', $expectedSha256 = '')
    {
        $configured = trim((string) getenv('PRIVATE_STORAGE_PATH'));
        if (ENVIRONMENT === 'production' && $configured === '') return FALSE;
        $root = realpath($configured !== '' ? $configured : FCPATH . 'storage');
        $real = realpath((string) $path);
        $prefix = $root !== FALSE ? rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';
        if ($root === FALSE || $real === FALSE || !is_file($real) || !is_readable($real)
            || ($real !== $root && strpos($real, $prefix) !== 0) || is_link((string) $path)) return FALSE;
        $size = @filesize($real);
        if ($size === FALSE || $size < 1 || $size > 8 * 1024 * 1024) return FALSE;
        $body = @file_get_contents($real);
        if (!is_string($body) || ($expectedSha256 !== '' && (!preg_match('/^[a-f0-9]{64}$/', $expectedSha256) || !hash_equals($expectedSha256, hash('sha256', $body))))) return FALSE;
        require_once APPPATH . 'libraries/Official_letter_html.php';
        if (!Official_letter_html::valid($body)) return FALSE;
        $name = $this->private_file_name($originalName, 'html', $real);
        $disposition = in_array($disposition, array('inline', 'attachment'), TRUE) ? $disposition : 'inline';
        $this->output->set_status_header(200)->set_content_type('text/html', 'utf-8')
            ->set_header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"')
            ->set_header('Content-Length: ' . (int) $size)->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header("Content-Security-Policy: default-src 'none'; img-src data:; style-src 'unsafe-inline'; script-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'")
            ->set_output($body);
        return TRUE;
    }

    private function private_file_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && trim($mime) !== '') {
                    return strtolower(trim($mime));
                }
            }
        }
        if (function_exists('mime_content_type')) {
            return strtolower(trim((string) mime_content_type($path)));
        }
        return '';
    }

    private function private_file_name($name, $extension, $path)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
        $name = trim((string) $name, '._-');
        if ($name === '') {
            $name = 'berkas-' . substr(hash('sha256', (string) $path), 0, 12);
        }
        if (!preg_match('/\.' . preg_quote($extension, '/') . '$/i', $name)) {
            $name .= '.' . $extension;
        }
        return substr($name, 0, 180);
    }
}

class Public_Controller extends MY_Controller
{
}

class App_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->currentUser) {
            $this->session->set_userdata('intended_url', current_url());
            redirect('login');
        }
    }
}

class Citizen_Controller extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (warga_is_staff($this->currentUser)) redirect('petugas');
    }
}

class Staff_Controller extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!warga_is_staff($this->currentUser)) redirect('dashboard');
    }
}
