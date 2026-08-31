<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$configuredUrl = getenv('APP_URL');
if ($configuredUrl) {
    $config['base_url'] = rtrim($configuredUrl, '/') . '/';
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '/smartdesa-warga';
    $config['base_url'] = $scheme . '://' . $host . rtrim(str_replace('\\', '/', $script), '/') . '/';
}
$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'indonesia';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';
$config['enable_query_strings'] = FALSE;
$config['allow_get_array'] = TRUE;
$config['log_threshold'] = ENVIRONMENT === 'development' ? 1 : 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';
$config['cache_path'] = '';
$config['cache_query_string'] = FALSE;
$appKey = trim((string) (getenv('APP_KEY') ?: ''));
if (ENVIRONMENT === 'production') {
    $publicParts = parse_url(trim((string) getenv('APP_URL')));
    $centralParts = parse_url(trim((string) getenv('WARGA_CENTRAL_API_URL')));
    $invalidKey = $appKey === '' || strlen($appKey) < 32 || stripos($appKey, 'ganti-dengan') !== FALSE || stripos($appKey, 'replace-with') !== FALSE || stripos($appKey, 'change-before') !== FALSE;
    $invalidPublicUrl = !is_array($publicParts) || strtolower(isset($publicParts['scheme']) ? $publicParts['scheme'] : '') !== 'https' || empty($publicParts['host']);
    $invalidCentralUrl = !is_array($centralParts) || strtolower(isset($centralParts['scheme']) ? $centralParts['scheme'] : '') !== 'https' || empty($centralParts['host']) || rtrim(isset($centralParts['path']) ? $centralParts['path'] : '', '/') !== '/v1';
    $storagePath = trim((string) getenv('PRIVATE_STORAGE_PATH'));
    $storageReal = $storagePath !== '' ? realpath($storagePath) : FALSE;
    $publicReal = realpath(FCPATH);
    $invalidStorage = $storageReal === FALSE || !is_dir($storageReal) || !is_readable($storageReal) || !is_writable($storageReal)
        || ($publicReal !== FALSE && strpos(rtrim($storageReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, rtrim($publicReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0);
    if ($invalidKey || getenv('WARGA_DEMO_MODE') !== '0' || $invalidPublicUrl || $invalidCentralUrl || $invalidStorage) {
        header('HTTP/1.1 503 Service Unavailable', TRUE, 503);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Konfigurasi aplikasi produksi belum lengkap.');
    }
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
$config['encryption_key'] = $appKey !== '' ? $appKey : hash('sha256', FCPATH . '|smartdesa-warga');
$config['sess_driver'] = 'files';
$config['sess_cookie_name'] = 'smartdesa_warga_session';
$config['sess_expiration'] = 2592000;
$config['sess_save_path'] = APPPATH . 'sessions';
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = TRUE;
$config['cookie_prefix'] = 'sdw_';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$config['cookie_httponly'] = TRUE;
$config['cookie_samesite'] = 'Lax';
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'sdw_csrf_token';
$config['csrf_cookie_name'] = 'sdw_csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = FALSE;
$config['csrf_exclude_uris'] = array('api/health');
$config['compress_output'] = FALSE;
$config['time_reference'] = 'local';
$config['rewrite_short_tags'] = FALSE;
$config['proxy_ips'] = '';
