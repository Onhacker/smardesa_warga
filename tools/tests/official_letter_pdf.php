<?php

$root = dirname(__DIR__, 2);
define('BASEPATH', $root . '/system/');
define('APPPATH', $root . '/application/');
require $root . '/vendor/autoload.php';
require APPPATH . 'libraries/Official_letter_html.php';
require APPPATH . 'libraries/Official_letter_pdf.php';

$policy = Official_letter_html::POLICY;
$html = '<!doctype html><html lang="id"><head><meta charset="utf-8">'
    . '<meta http-equiv="Content-Security-Policy" content="' . $policy . '">'
    . '<title>Surat Resmi</title><style>@page{size:A4 portrait;margin:18mm}'
    . 'body{font-family:Arial,sans-serif;font-size:12pt;color:#111}h1{text-align:center}'
    . 'table{width:100%;border-collapse:collapse}td{padding:4px;border:1px solid #333}</style>'
    . '</head><body><main><h1>Surat Keterangan</h1><p>Dokumen uji SI DAPULIK.</p>'
    . '<table><tr><td>Nomor</td><td>001/TEST/2026</td></tr></table></main></body></html>';

if (!Official_letter_html::valid($html)) {
    fwrite(STDERR, "FAIL: HTML uji tidak lolos validasi.\n");
    exit(1);
}

try {
    $pdf = Official_letter_pdf::render($html, $root . '/storage');
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . "\n");
    exit(1);
}

if (substr($pdf, 0, 5) !== '%PDF-' || strlen($pdf) < 500) {
    fwrite(STDERR, 'FAIL: Keluaran bukan PDF yang valid (' . strlen($pdf) . " byte).\n");
    exit(1);
}

$outputPath = trim((string) getenv('SDW_PDF_OUTPUT'));
if ($outputPath !== '' && @file_put_contents($outputPath, $pdf) !== strlen($pdf)) {
    fwrite(STDERR, "FAIL: PDF uji tidak dapat ditulis.\n");
    exit(1);
}

echo 'PASS official letter PDF (' . strlen($pdf) . " byte)\n";
