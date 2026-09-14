<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $pages = max(0, (int) ($pages ?? 0)); $page = max(1, (int) ($page ?? 1)); ?>
<?php if ($pages > 1): ?>
<nav class="community-pagination" data-complaint-pagination aria-label="Halaman pengaduan">
    <button type="button" class="community-pagination-button" data-complaint-page="<?= $page - 1 ?>"<?= $page <= 1 ? ' disabled' : '' ?> aria-label="Halaman sebelumnya"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
    <span class="community-pagination-label">Halaman <?= $page ?> dari <?= $pages ?></span>
    <button type="button" class="community-pagination-button" data-complaint-page="<?= $page + 1 ?>"<?= $page >= $pages ? ' disabled' : '' ?> aria-label="Halaman berikutnya"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
</nav>
<?php endif; ?>
