<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['login'] = 'auth/login';
$route['register'] = 'auth/register';
$route['logout'] = 'auth/logout';
$route['dashboard'] = 'dashboard/index';
$route['permohonan'] = 'permohonan/index';
$route['permohonan/baru'] = 'permohonan/create';
$route['permohonan/simpan'] = 'permohonan/store';
$route['permohonan/(:any)/surat'] = 'permohonan/document/$1';
$route['permohonan/(:any)'] = 'permohonan/show/$1';
$route['petugas'] = 'petugas/index';
$route['petugas/permohonan/(:any)/tindakan'] = 'petugas/action/$1';
$route['petugas/permohonan/(:any)'] = 'petugas/show/$1';
$route['petugas/berkas/(:any)'] = 'petugas/document/$1';
$route['notifikasi'] = 'notifications/index';
$route['akun'] = 'account/index';
$route['api/health'] = 'api/health';
