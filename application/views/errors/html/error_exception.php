<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $heading = isset($heading) ? $heading : 'Terjadi kesalahan'; $message = ENVIRONMENT === 'development' && isset($exception) ? $exception->getMessage() : 'Layanan sedang mengalami kendala. Silakan coba kembali.'; include __DIR__ . '/error_general.php'; ?>
