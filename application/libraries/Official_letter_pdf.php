<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Render a validated official-letter HTML snapshot as a PDF.
 *
 * The HTML snapshot is deliberately validated before it reaches Dompdf.  This
 * keeps the PDF endpoint subject to the same passive-document rules as the
 * existing browser preview while allowing the preview itself to remain HTML.
 */
class Official_letter_pdf
{
    public static function render($html, $chroot = '')
    {
        if (!is_string($html) || $html === '') {
            throw new InvalidArgumentException('Surat kosong.');
        }

        require_once APPPATH . 'libraries/Official_letter_html.php';
        if (!Official_letter_html::valid($html)) {
            throw new InvalidArgumentException('Format surat tidak aman.');
        }
        if (!class_exists('Dompdf\\Dompdf') || !class_exists('Dompdf\\Options')) {
            throw new RuntimeException('Mesin PDF belum tersedia.');
        }

        $options = new \Dompdf\Options(array(
            'isRemoteEnabled' => FALSE,
            'isPhpEnabled' => FALSE,
            'isJavascriptEnabled' => FALSE,
            'defaultMediaType' => 'print',
            'defaultFont' => 'DejaVu Sans',
            'isFontSubsettingEnabled' => TRUE
        ));
        // Validated snapshots only embed images as data URIs. Remove file and
        // network protocols as an additional defence around the renderer.
        $options->setAllowedProtocols(array('data://'));
        $root = realpath((string) $chroot);
        if ($root !== FALSE && is_dir($root)) {
            $options->setChroot($root);
        }

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();
        if (!is_string($pdf) || strlen($pdf) < 64 || substr($pdf, 0, 5) !== '%PDF-') {
            throw new RuntimeException('PDF tidak berhasil dibuat.');
        }
        return $pdf;
    }
}
