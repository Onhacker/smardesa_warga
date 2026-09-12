<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $isNotificationList = $listKind === 'notifications'; ?>
<form method="get" action="<?= e($listUrl) ?>" class="warga-list-search" data-list-search aria-label="Pencarian <?= $listKind === 'notifications' ? 'pemberitahuan' : 'permohonan' ?>">
    <div class="warga-list-search-fields">
        <div class="warga-list-field">
            <label for="warga-list-name"><?= $isNotificationList ? 'Judul atau isi' : 'Nama surat' ?></label>
            <div class="warga-list-input"><i class="fa fa-search" aria-hidden="true"></i><input type="search" id="warga-list-name" name="q" value="<?= e($listing['filters']['q']) ?>" placeholder="<?= $isNotificationList ? 'Cari pemberitahuan' : 'Cari nama surat' ?>" maxlength="180"></div>
        </div>
        <div class="warga-list-field">
            <label for="warga-list-date"><?= e($dateLabel) ?></label>
            <div class="warga-list-input"><input type="date" id="warga-list-date" name="date" value="<?= e($listing['filters']['date']) ?>"></div>
        </div>
        <div class="warga-list-search-actions">
            <button type="submit" class="warga-list-search-submit"><i class="fa fa-search" aria-hidden="true"></i> Cari</button>
            <a href="<?= e($listUrl) ?>" class="warga-list-reset" data-list-reset>Reset</a>
        </div>
    </div>
    <?php if ($listKind === 'requests'): ?>
        <input type="hidden" name="status" value="<?= e($listing['filters']['status']) ?>">
        <nav class="warga-list-status" aria-label="Filter status permohonan">
            <?php foreach (array('all' => 'Semua', 'active' => 'Diproses', 'issued' => 'Selesai') as $value => $label): ?>
                <?php $filterQuery = $listing['filters']; $filterQuery['status'] = $value; ?>
                <a href="<?= e($listUrl . '?' . http_build_query($filterQuery)) ?>" data-list-filter="<?= e($value) ?>" class="<?= $listing['filters']['status'] === $value ? 'active' : '' ?>" <?= $listing['filters']['status'] === $value ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</form>
<p class="warga-list-feedback" data-list-feedback role="status" aria-live="polite" aria-atomic="true"></p>
<div class="warga-list-error" data-list-error role="alert" hidden>
    <span data-list-error-message></span>
    <button type="button" data-list-retry>Coba lagi</button>
    <a href="<?= site_url('login') ?>" data-list-login hidden>Masuk kembali</a>
</div>
