<?php
if (PHP_SAPI !== 'cli') exit(1);

define('BASEPATH', dirname(__DIR__, 2) . '/system/');
require dirname(__DIR__, 2) . '/application/helpers/warga_helper.php';

function preparation_check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL {$message}\n");
        exit(1);
    }
    echo "PASS {$message}\n";
}

$items = warga_service_preparation_items(array(
    'requirements' => array(
        'Nomor Kartu Keluarga',
        array('label' => 'Berkas: KTP')
    ),
    'form_schema' => array('fields' => array(
        array('key' => 'kk', 'label' => 'Nomor Kartu Keluarga', 'type' => 'text', 'required' => TRUE),
        array('key' => 'ktp', 'label' => 'KTP', 'type' => 'file', 'required' => TRUE),
        array('key' => 'pasfoto', 'label' => 'Pasfoto', 'type' => 'file', 'required' => FALSE),
        array('key' => 'catatan', 'label' => 'Catatan opsional', 'type' => 'text', 'required' => FALSE)
    ))
));

preparation_check($items === array(
    'Nomor Kartu Keluarga',
    'Berkas: KTP',
    'Berkas: Pasfoto (opsional)'
), 'persyaratan lama dan field skema digabung tanpa duplikasi');

$schemaOnly = warga_service_preparation_items(array(
    'form_schema' => array('fields' => array(
        array('label' => 'Nama Orang Tua', 'type' => 'text', 'required' => '1'),
        array('label' => 'Surat Pengantar', 'type' => 'file', 'required' => 'false')
    ))
));
preparation_check($schemaOnly === array(
    'Nama Orang Tua',
    'Berkas: Surat Pengantar (opsional)'
), 'katalog tanpa requirements tetap menampilkan data wajib dan semua berkas');

echo "OK: checklist persiapan permohonan lengkap.\n";
