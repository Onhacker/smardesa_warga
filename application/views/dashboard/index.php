<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$citizenVerified = !empty($citizenVerified);
$lettersPage = !empty($lettersPage);
?>
<?php if ($lettersPage): ?>
<section class="warga-home-head warga-letters-head" aria-labelledby="warga-letters-title">
    <span class="warga-intro-icon" aria-hidden="true"><i class="fa fa-envelope"></i></span>
    <div class="warga-letters-copy">
        <h1 id="warga-letters-title">Surat Menyurat</h1>
        <p class="warga-letters-subtitle">Pelayanan Permohonan Surat</p>
    </div>
</section>
<?php endif; ?>
<nav class="warga-letter-shortcuts" aria-label="Tindakan surat">
    <a class="is-history" href="<?= site_url('permohonan') ?>"><i class="fa fa-history" aria-hidden="true"></i> Riwayat</a>
    <a class="is-create" href="<?= site_url('layanan') ?>"><i class="fa fa-plus" aria-hidden="true"></i> Ajukan Surat</a>
</nav>
<?php if (!$lettersPage): ?><section class="warga-home-head">
    <div class="warga-home-identity">
        <span class="warga-avatar"><?= e(warga_initials($currentUser['name'])) ?></span>
        <div><p class="color-white">Selamat datang</p><h1><?= e($currentUser['name']) ?></h1><span class="color-white"><i class="fa fa-map-marker-alt"></i> <?= e($currentUser['village_name']) ?></span></div>
    </div>
    <a href="<?= site_url('notifikasi') ?>" class="warga-head-action" aria-label="Buka pemberitahuan"><i class="fa fa-bell"></i></a>
</section><?php endif; ?>

<section class="warga-summary-band" aria-label="Ringkasan permohonan">
    <a href="<?= site_url('permohonan') ?>" class="warga-summary-card is-total" aria-label="Lihat semua <?= (int) $summary['total'] ?> permohonan">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-file-alt"></i></span>
        <strong><?= number_format($summary['total']) ?></strong><span class="warga-summary-label">Total</span>
    </a>
    <a href="<?= e(site_url('permohonan') . '?status=active') ?>" class="warga-summary-card is-active" aria-label="Lihat <?= (int) $summary['active'] ?> permohonan yang diproses">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-clock"></i></span>
        <strong><?= number_format($summary['active']) ?></strong><span class="warga-summary-label">Diproses</span>
    </a>
    <a href="<?= e(site_url('permohonan') . '?status=issued') ?>" class="warga-summary-card is-issued" aria-label="Lihat <?= (int) $summary['issued'] ?> permohonan yang selesai">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-check-circle"></i></span>
        <strong><?= number_format($summary['issued']) ?></strong><span class="warga-summary-label">Selesai</span>
    </a>
    <a href="<?= e(site_url('permohonan') . '?status=revision') ?>" class="warga-summary-card is-revision" aria-label="Lihat <?= (int) $summary['revision'] ?> permohonan yang perlu diperbaiki">
        <span class="warga-summary-icon" aria-hidden="true"><i class="fa fa-wrench"></i></span>
        <strong><?= number_format($summary['revision']) ?></strong><span class="warga-summary-label">Perbaikan</span>
    </a>
</section>

<section class="warga-service-card warga-dashboard-services" aria-labelledby="warga-service-title">
    <div class="content warga-section-head warga-service-heading-card">
        <div><p class="font-600 color-highlight mb-n1">Pelayanan <?= e($institutionLower) ?></p><h2 id="warga-service-title" class="font-22 mb-0">Ajukan Surat</h2></div>
        <a href="<?= site_url('layanan') ?>" class="font-12 color-highlight font-600">Semua Surat</a>
    </div>
    <div class="warga-service-grid" id="wargaServiceGrid" aria-label="Jenis layanan">
        <?php foreach ($services as $index => $service): ?>
            <?php $dashboardServiceName = !empty($service['short_name']) ? $service['short_name'] : $service['name']; $serviceIcon = warga_service_icon($service); ?>
            <?php if ($citizenVerified): ?>
                <a href="<?= site_url('permohonan/baru?layanan=' . rawurlencode($service['slug'])) ?>" class="warga-service-item" title="<?= e($service['name']) ?>">
                    <span class="warga-service-icon <?= e($serviceIcon['class']) ?>"><i class="<?= e($serviceIcon['icon']) ?>"></i></span>
                    <strong><?= e($dashboardServiceName) ?></strong>
                </a>
            <?php else: ?>
                <span class="warga-service-item is-locked" aria-disabled="true" title="<?= e($service['name']) ?>">
                    <span class="warga-service-icon <?= e($serviceIcon['class']) ?>"><i class="<?= e($serviceIcon['icon']) ?>"></i></span>
                    <strong><?= e($dashboardServiceName) ?></strong>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php if (!$services): ?><p class="warga-service-empty">Belum ada layanan dari <?= e($institutionLower) ?>.</p><?php endif; ?>
</section>

<section class="content warga-section-head warga-activity-head mt-4">
    <div><p class="font-600 color-highlight mb-n1">Aktivitas terbaru</p><h2 class="font-22 mb-0">Permohonan Saya</h2></div>
    <a href="<?= site_url('permohonan') ?>" class="font-12 color-highlight font-600">Lihat semua</a>
</section>
<section class="warga-request-list warga-activity-list">
    <?php if (!$requests): ?>
        <div class="warga-empty-state"><span><i class="fa fa-file-alt"></i></span><h3>Belum ada permohonan</h3><?php if ($citizenVerified): ?><a href="<?= site_url('layanan') ?>" class="btn btn-s bg-teal-dark color-white rounded-s">Ajukan Surat</a><?php endif; ?></div>
    <?php endif; ?>
    <?php foreach (array_slice($requests, 0, 3) as $request): ?>
        <?php $requestIcon = warga_request_service_icon($request); ?>
        <a href="<?= site_url('permohonan/' . rawurlencode($request['id'])) ?>" class="warga-request-card">
            <span class="warga-request-icon <?= e($requestIcon['class']) ?>"><i class="<?= e($requestIcon['icon']) ?>" aria-hidden="true"></i></span>
            <span class="warga-request-copy"><strong><?= e($request['service_name']) ?></strong><small><?= e($request['request_code']) ?> · <?= e(tanggal_id($request['submitted_at'])) ?></small></span>
            <span class="warga-request-status"><?= warga_status_label($request['status']) ?><i class="fa fa-chevron-right"></i></span>
        </a>
    <?php endforeach; ?>
</section>

<?php if (!$citizenVerified): ?>
<section class="warga-verification-notice" role="status">
    <i class="fa fa-user-shield"></i>
    <div><strong>Akun belum terverifikasi</strong><p>Akun lama ini belum cocok dengan Data Penduduk <?= e($institutionLower) ?>. Hubungi operator <?= e($institutionLower) ?> agar data penduduk disinkronkan, lalu daftarkan akun warga menggunakan NIK, No. KK, dan nama yang sesuai.</p></div>
</section>
<?php endif; ?>
