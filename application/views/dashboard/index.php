<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$citizenVerified = !empty($citizenVerified);
?>
<section class="warga-home-head">
    <div class="warga-home-identity">
        <span class="warga-avatar"><?= e(warga_initials($currentUser['name'])) ?></span>
        <div><p class="color-white">Selamat datang</p><h1><?= e($currentUser['name']) ?></h1><span class="color-white"><i class="fa fa-map-marker-alt"></i> <?= e($currentUser['village_name']) ?></span></div>
    </div>
    <a href="<?= site_url('notifikasi') ?>" class="warga-head-action" aria-label="Buka notifikasi"><i class="fa fa-bell"></i></a>
</section>

<section class="warga-summary-band" aria-label="Ringkasan permohonan">
    <div class="warga-summary-card is-total">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-file-alt"></i></span>
        <strong><?= number_format($summary['total']) ?></strong><span class="warga-summary-label">Total</span>
    </div>
    <div class="warga-summary-card is-active">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-clock"></i></span>
        <strong><?= number_format($summary['active']) ?></strong><span class="warga-summary-label">Diproses</span>
    </div>
    <div class="warga-summary-card is-issued">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-check-circle"></i></span>
        <strong><?= number_format($summary['issued']) ?></strong><span class="warga-summary-label">Selesai</span>
    </div>
    <div class="warga-summary-card is-revision">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-wrench"></i></span>
        <strong><?= number_format($summary['revision']) ?></strong><span class="warga-summary-label">Perbaikan</span>
    </div>
</section>

<section class="warga-service-card" aria-labelledby="warga-service-title">
    <div class="content warga-section-head warga-service-heading-card">
        <div><p class="font-600 color-highlight mb-n1">Pelayanan desa</p><h2 id="warga-service-title" class="font-22 mb-0">Ajukan Surat</h2></div>
        <?php if ($citizenVerified): ?>
            <a href="<?= site_url('permohonan/baru') ?>" class="font-12 color-highlight font-600">Semua layanan</a>
        <?php else: ?>
            <span class="warga-service-locked" aria-label="Layanan terkunci"><i class="fa fa-lock"></i></span>
        <?php endif; ?>
    </div>
    <?php if ($services): ?>
    <div class="warga-service-search">
        <label for="wargaServiceSearch">Cari surat</label>
        <div class="warga-service-search-input">
            <i class="fa fa-search" aria-hidden="true"></i>
            <input type="search" id="wargaServiceSearch" placeholder="Nama atau jenis surat" data-service-search aria-controls="wargaServiceGrid" autocomplete="off">
        </div>
        <span class="warga-service-count" data-service-count aria-live="polite"><?= count($services) ?> layanan</span>
    </div>
    <?php endif; ?>
    <div class="warga-service-grid" id="wargaServiceGrid" aria-label="Jenis layanan">
        <?php $serviceColors = array('teal', 'blue', 'orange', 'green', 'red'); ?>
        <?php foreach ($services as $index => $service): ?>
            <?php $serviceSearch = $service['name'] . ' ' . $service['short_name'] . ' ' . $service['slug']; ?>
            <?php if ($citizenVerified): ?>
                <a href="<?= site_url('permohonan/baru?layanan=' . rawurlencode($service['slug'])) ?>" class="warga-service-item" data-service-name="<?= e($serviceSearch) ?>" title="<?= e($service['name']) ?>">
                    <span class="warga-service-icon is-<?= e($serviceColors[$index % count($serviceColors)]) ?>"><i class="fa <?= e($service['icon']) ?>"></i></span>
                    <strong><?= e($service['name']) ?></strong>
                </a>
            <?php else: ?>
                <span class="warga-service-item is-locked" aria-disabled="true" data-service-name="<?= e($serviceSearch) ?>">
                    <span class="warga-service-icon is-<?= e($serviceColors[$index % count($serviceColors)]) ?>"><i class="fa <?= e($service['icon']) ?>"></i></span>
                    <strong><?= e($service['name']) ?></strong>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <p class="warga-service-empty" data-service-empty <?= $services ? 'hidden' : '' ?>><?= $services ? 'Surat tidak ditemukan.' : 'Belum ada layanan dari desa.' ?></p>
</section>

<section class="content warga-section-head warga-activity-head mt-4">
    <div><p class="font-600 color-highlight mb-n1">Aktivitas terbaru</p><h2 class="font-22 mb-0">Permohonan Saya</h2></div>
    <a href="<?= site_url('permohonan') ?>" class="font-12 color-highlight font-600">Lihat semua</a>
</section>
<section class="warga-request-list warga-activity-list">
    <?php if (!$requests): ?>
        <div class="warga-empty-state"><span><i class="fa fa-file-alt"></i></span><h3>Belum ada permohonan</h3><?php if ($citizenVerified): ?><a href="<?= site_url('permohonan/baru') ?>" class="btn btn-s bg-teal-dark color-white rounded-s">Ajukan Surat</a><?php endif; ?></div>
    <?php endif; ?>
    <?php foreach (array_slice($requests, 0, 3) as $request): ?>
        <a href="<?= site_url('permohonan/' . rawurlencode($request['id'])) ?>" class="warga-request-card">
            <span class="warga-request-icon"><i class="fa <?= e($request['service_icon']) ?>"></i></span>
            <span class="warga-request-copy"><strong><?= e($request['service_name']) ?></strong><small><?= e($request['request_code']) ?> · <?= e(tanggal_id($request['submitted_at'])) ?></small></span>
            <span class="warga-request-status"><?= warga_status_label($request['status']) ?><i class="fa fa-chevron-right"></i></span>
        </a>
    <?php endforeach; ?>
</section>

<?php if (!$citizenVerified): ?>
<section class="warga-verification-notice" role="status">
    <i class="fa fa-user-shield"></i>
    <div><strong>Akun belum terverifikasi</strong><p>Akun lama ini belum cocok dengan Data Penduduk kampung/desa. Hubungi operator desa agar data penduduk disinkronkan, lalu daftarkan akun warga menggunakan NIK, No. KK, dan nama yang sesuai.</p></div>
</section>
<?php endif; ?>

<section class="warga-home-notice">
    <i class="fa fa-sync-alt"></i><div><strong>Sinkronisasi desa</strong><p>Status permohonan diperbarui otomatis saat perangkat SmartDesa desa terhubung.</p></div>
</section>
