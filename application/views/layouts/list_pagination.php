<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-list-pagination">
    <p data-list-summary><?= $listing['total'] ? 'Menampilkan ' . (int) $listing['from'] . '–' . (int) $listing['to'] . ' dari ' . (int) $listing['total'] . ' data' : '0 data ditemukan' ?></p>
    <nav aria-label="Halaman hasil" class="warga-list-pages">
        <?php $pageQuery = $listing['filters']; ?>
        <?php if ($listing['page'] > 1): ?>
            <?php $pageQuery['page'] = $listing['page'] - 1; ?>
            <a href="<?= e($listUrl . '?' . http_build_query($pageQuery)) ?>" data-list-page aria-label="Halaman sebelumnya" rel="prev"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
        <?php else: ?>
            <span class="is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya"><i class="fa fa-chevron-left" aria-hidden="true"></i></span>
        <?php endif; ?>
        <span class="warga-list-page-number" aria-current="page">Halaman <?= (int) $listing['page'] ?> / <?= (int) $listing['pages'] ?></span>
        <?php if ($listing['page'] < $listing['pages']): ?>
            <?php $pageQuery['page'] = $listing['page'] + 1; ?>
            <a href="<?= e($listUrl . '?' . http_build_query($pageQuery)) ?>" data-list-page aria-label="Halaman berikutnya" rel="next"><i class="fa fa-chevron-right" aria-hidden="true"></i></a>
        <?php else: ?>
            <span class="is-disabled" aria-disabled="true" aria-label="Halaman berikutnya"><i class="fa fa-chevron-right" aria-hidden="true"></i></span>
        <?php endif; ?>
    </nav>
</div>
