<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$autoload['packages'] = array();
$autoload['libraries'] = array('session', 'form_validation');
if (getenv('WARGA_DEMO_MODE') !== '1') $autoload['libraries'][] = 'database';
$autoload['drivers'] = array();
$autoload['helper'] = array('url', 'form', 'security', 'warga');
$autoload['config'] = array();
$autoload['language'] = array();
$autoload['model'] = array();
