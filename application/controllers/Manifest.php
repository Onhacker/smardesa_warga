<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manifest extends CI_Controller
{
    public function index()
    {
        $this->load->model('Branding_model');
        $branding = $this->Branding_model->current();
        $scope = (string) parse_url(base_url(), PHP_URL_PATH);
        $scope = '/' . trim($scope, '/') . '/';
        if ($scope === '//') $scope = '/';
        $asset = static function ($path) use ($scope) {
            return $scope . ltrim((string) $path, '/');
        };
        $manifest = array(
            'id' => $scope,
            'name' => $branding['nama_sistem'],
            'short_name' => $branding['nama_sistem'],
            'description' => $branding['kepanjangan'] !== '' ? $branding['kepanjangan'] : $branding['tagline'],
            'lang' => 'id-ID',
            'dir' => 'ltr',
            'start_url' => $asset('dashboard'),
            'scope' => $scope,
            'display' => 'standalone',
            'display_override' => array('standalone', 'minimal-ui'),
            'orientation' => 'portrait-primary',
            'background_color' => '#eef3f5',
            'theme_color' => '#235fa4',
            'categories' => array('government', 'productivity'),
            'prefer_related_applications' => FALSE,
            'icons' => array(
                array('src' => $asset('assets/pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
                array('src' => $asset('assets/pwa/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
                array('src' => $asset('assets/pwa/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
                array('src' => $asset('assets/pwa/notification-badge.png?v=20260913-badge-2'), 'sizes' => '96x96', 'type' => 'image/png', 'purpose' => 'monochrome')
            ),
            'shortcuts' => array(
                array('name' => 'Ajukan Surat', 'short_name' => 'Ajukan', 'url' => $asset('permohonan/baru'), 'icons' => array(array('src' => $asset('assets/pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'))),
                array('name' => 'Permohonan Saya', 'short_name' => 'Riwayat', 'url' => $asset('permohonan'), 'icons' => array(array('src' => $asset('assets/pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png')))
            )
        );

        $this->output
            ->set_header('Cache-Control: public, max-age=300, must-revalidate')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_content_type('application/manifest+json', 'utf-8')
            ->set_output(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
