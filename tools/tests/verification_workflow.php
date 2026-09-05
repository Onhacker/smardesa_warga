<?php
if (PHP_SAPI !== 'cli') exit(1);
define('BASEPATH', dirname(__DIR__, 2) . '/system/');
require dirname(__DIR__, 2) . '/application/libraries/Verification_workflow.php';

function check_flow($actual, $expected, $label)
{
    sort($actual); sort($expected);
    if ($actual !== $expected) throw new RuntimeException($label . ': ' . json_encode($actual));
    echo "PASS {$label}\n";
}

check_flow(Verification_workflow::actions('sekdes', 'submitted', array('sekdes'=>true,'kades'=>true)),
    array('verify','revision','reject'), 'Sekdes memverifikasi saat dua tahap aktif');
check_flow(Verification_workflow::actions('kepala-desa', 'verified', array('sekdes'=>true,'kades'=>true)),
    array('approve','revision','reject'), 'Kades menyetujui setelah verifikasi Sekdes');
check_flow(Verification_workflow::actions('kepala-desa', 'submitted', array('sekdes'=>false,'kades'=>true)),
    array('approve','revision','reject'), 'Kades langsung menyetujui saat Sekdes nonaktif');
check_flow(Verification_workflow::actions('sekdes', 'submitted', array('sekdes'=>true,'kades'=>false)),
    array('approve','revision','reject'), 'Sekdes langsung menyetujui saat Kades nonaktif');
check_flow(Verification_workflow::actions('kepala-desa', 'submitted', array('sekdes'=>false,'kades'=>false)),
    array(), 'Tidak ada verifikasi PWA saat kedua tahap nonaktif');
check_flow(Verification_workflow::actions('warga', 'submitted', array('sekdes'=>true,'kades'=>true)),
    array(), 'Warga tidak dapat memproses permohonan');
echo "OK: verification workflow checks passed.\n";
