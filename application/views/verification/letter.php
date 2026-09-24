<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$verified = is_array($verification);
$is_local = $verified && isset($verification['verification_type'])
    && (string) $verification['verification_type'] === 'local';
?>
<style>
    .warga-verification { max-width: 760px; margin: 0 auto; padding: 8px 0 28px; }
    .warga-verification-hero { display: flex; align-items: center; gap: 14px; padding: 18px; border: 1px solid <?= $verified ? '#b7dfc3' : '#f1b8b8' ?>; border-radius: 8px; background: <?= $verified ? '#f1fbf4' : '#fff5f5' ?>; }
    .warga-verification-icon { flex: 0 0 48px; width: 48px; height: 48px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: <?= $verified ? '#238443' : '#bb2638' ?>; font-size: 23px; }
    .warga-verification-hero h1 { margin: 0 0 4px; font-size: 23px; line-height: 1.2; color: #14243a; }
    .warga-verification-hero p { margin: 0; color: #52657c; font-size: 14px; }
    .warga-verification-section { margin-top: 14px; padding: 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .warga-verification-section h2 { margin: 0 0 14px; font-size: 17px; color: #14243a; }
    .warga-verification-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 24px; margin: 0; }
    .warga-verification-grid div { min-width: 0; }
    .warga-verification-grid dt { margin: 0 0 3px; color: #65758b; font-size: 12px; font-weight: 600; }
    .warga-verification-grid dd { margin: 0; color: #14243a; font-size: 15px; font-weight: 600; overflow-wrap: anywhere; }
    .warga-verification-note { color: #52657c; font-size: 13px; line-height: 1.55; }
    .warga-verification-actions { margin-top: 16px; text-align: center; }
    @media (max-width: 560px) { .warga-verification-grid { grid-template-columns: 1fr; } .warga-verification-hero { align-items: flex-start; } }
</style>

<article class="warga-verification" aria-labelledby="verification-title">
    <header class="warga-verification-hero">
        <div class="warga-verification-icon" aria-hidden="true"><i class="fa fa-<?= $verified ? 'check' : 'exclamation' ?>"></i></div>
        <div>
            <h1 id="verification-title"><?= $verified ? 'Surat resmi terverifikasi' : 'Dokumen tidak dapat diverifikasi' ?></h1>
            <p><?= $verified
                ? ($is_local
                    ? 'Data surat cocok dengan catatan penerbit yang diterima layanan pusat.'
                    : 'Data surat cocok dengan catatan penerbit dan berkas resminya.')
                : 'Tautan atau data surat tidak ditemukan pada layanan pusat.' ?></p>
        </div>
    </header>

    <?php if ($verified): ?>
        <section class="warga-verification-section" aria-labelledby="verification-data-title">
            <h2 id="verification-data-title">Informasi dokumen</h2>
            <dl class="warga-verification-grid">
                <div><dt>Kode permohonan</dt><dd><?= e($verification['request_code']) ?></dd></div>
                <div><dt>Nomor surat</dt><dd><?= e($verification['local_reference']) ?></dd></div>
                <div><dt>Layanan</dt><dd><?= e($verification['service_name']) ?></dd></div>
                <div><dt>Diterbitkan</dt><dd><?= e(tanggal_id($verification['issued_at'], TRUE)) ?></dd></div>
                <div><dt><?= e($institutionLabel) ?></dt><dd><?= e($verification['village_name']) ?></dd></div>
                <div><dt><?= e($districtLabel) ?> / Kabupaten</dt><dd><?= e($verification['district_name']) ?> / <?= e($verification['regency_name']) ?></dd></div>
            </dl>
        </section>
        <section class="warga-verification-section">
            <h2><?= $is_local ? 'Keaslian catatan surat' : 'Keaslian berkas' ?></h2>
            <p class="warga-verification-note"><?= $is_local
                ? 'Catatan surat lokal cocok dengan metadata yang diterima layanan pusat. Pemeriksaan ini tidak membuka HTML, PDF, lampiran, atau data pribadi pemohon.'
                : 'Sidik digital berkas resmi cocok dengan catatan penerbit. Pemeriksaan ini hanya menampilkan metadata publik dan tidak membuka data pribadi pemohon.' ?></p>
            <dl class="warga-verification-grid">
                <div><dt>Sidik digital</dt><dd><?= e($verification['document_fingerprint']) ?></dd></div>
            </dl>
        </section>
    <?php else: ?>
        <section class="warga-verification-section">
            <h2>Periksa kembali QR surat</h2>
            <p class="warga-verification-note">Pastikan QR dipindai langsung dari dokumen resmi dan tautannya tidak diubah. Untuk bantuan, hubungi kantor penerbit surat.</p>
        </section>
    <?php endif; ?>

    <div class="warga-verification-actions">
        <a class="btn btn-m btn-full bg-theme color-white" href="<?= e(base_url()) ?>"><i class="fa fa-home me-2"></i>Kembali ke layanan warga</a>
    </div>
</article>
