<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$marketplaceReady = isset($marketplaceReady) ? (bool) $marketplaceReady : TRUE;
$canManage = !empty($canManage);
?>
<div class="market-empty-state" data-market-empty-state>
    <span class="market-empty-icon"><i class="fa <?= $marketplaceReady ? 'fa-store-slash' : 'fa-database' ?>" aria-hidden="true"></i></span>
    <h3><?= $marketplaceReady ? 'Belum ada produk' : 'Pasar Dapulik belum siap' ?></h3>
    <p><?= $marketplaceReady ? 'Produk warga akan tampil di sini setelah diterbitkan.' : 'Jalankan database/migrations/016_marketplace.sql di server untuk mengaktifkan katalog.' ?></p>
    <?php if ($canManage && $marketplaceReady): ?><a href="<?= site_url('pasar/buat') ?>" class="btn btn-s bg-blue-dark color-white rounded-s"><i class="fa fa-plus color-white" aria-hidden="true"></i> <span class="color-white">Tambah produk</span></a><?php endif; ?>
</div>
