<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$contactStoreName = trim((string) ($contactStoreName ?? 'Toko warga')) ?: 'Toko warga';
$contactVillageName = trim((string) ($contactVillageName ?? ''));
$contactWhatsappHref = trim((string) ($contactWhatsappHref ?? ''));
$contactWhatsappCallHref = trim((string) ($contactWhatsappCallHref ?? ''));
$contactPhoneHref = trim((string) ($contactPhoneHref ?? ''));
$contactHeading = trim((string) ($contactHeading ?? 'Hubungi penjual')) ?: 'Hubungi penjual';
$contactDescription = trim((string) ($contactDescription ?? 'Pilih cara yang paling nyaman untuk menghubungi penjual.')) ?: 'Pilih cara yang paling nyaman untuk menghubungi penjual.';
?>

<div class="market-contact-modal" data-market-contact-modal hidden>
    <button type="button" class="market-contact-backdrop" data-market-contact-close aria-label="Tutup pilihan kontak"></button>
    <section class="market-contact-dialog" id="market-contact-dialog" role="dialog" aria-modal="true" aria-labelledby="market-contact-title" aria-describedby="market-contact-description">
        <div class="market-contact-dialog-head">
            <div>
                <p class="market-eyebrow market-eyebrow-blue">KONTAK PENJUAL</p>
                <h2 id="market-contact-title"><?= e($contactHeading) ?></h2>
            </div>
            <button type="button" class="market-contact-close" data-market-contact-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="market-contact-seller">
            <span class="market-contact-seller-icon" aria-hidden="true"><i class="fa fa-store"></i></span>
            <span class="market-contact-seller-copy"><strong><?= e($contactStoreName) ?></strong><?php if ($contactVillageName !== ''): ?><small><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($contactVillageName) ?></small><?php endif; ?></span>
        </div>
        <p class="market-contact-description" id="market-contact-description"><?= e($contactDescription) ?></p>
        <div class="market-contact-options" aria-label="Pilihan kontak penjual">
            <?php if ($contactWhatsappHref !== ''): ?><a href="<?= e($contactWhatsappHref) ?>" class="market-contact-option is-whatsapp" data-market-contact-action target="_blank" rel="noopener"><span class="market-contact-option-icon"><i class="fa fa-comments" aria-hidden="true"></i></span><span><strong>Chat WhatsApp</strong><small>Kirim pesan kepada penjual</small></span><i class="fa fa-chevron-right" aria-hidden="true"></i></a><?php else: ?><span class="market-contact-option is-disabled" aria-disabled="true"><span class="market-contact-option-icon"><i class="fa fa-comments" aria-hidden="true"></i></span><span><strong>Chat WhatsApp</strong><small>Nomor WhatsApp belum tersedia</small></span></span><?php endif; ?>
            <?php if ($contactWhatsappCallHref !== ''): ?><a href="<?= e($contactWhatsappCallHref) ?>" class="market-contact-option is-whatsapp-call" data-market-contact-action><span class="market-contact-option-icon"><i class="fa fa-phone" aria-hidden="true"></i></span><span><strong>Telepon WhatsApp</strong><small>Panggilan suara melalui WhatsApp</small></span><i class="fa fa-chevron-right" aria-hidden="true"></i></a><?php else: ?><span class="market-contact-option is-disabled" aria-disabled="true"><span class="market-contact-option-icon"><i class="fa fa-phone" aria-hidden="true"></i></span><span><strong>Telepon WhatsApp</strong><small>Nomor WhatsApp belum tersedia</small></span></span><?php endif; ?>
            <?php if ($contactPhoneHref !== ''): ?><a href="<?= e($contactPhoneHref) ?>" class="market-contact-option is-phone" data-market-contact-action><span class="market-contact-option-icon"><i class="fa fa-phone" aria-hidden="true"></i></span><span><strong>Telepon</strong><small>Hubungi melalui jaringan seluler</small></span><i class="fa fa-chevron-right" aria-hidden="true"></i></a><?php else: ?><span class="market-contact-option is-disabled" aria-disabled="true"><span class="market-contact-option-icon"><i class="fa fa-phone" aria-hidden="true"></i></span><span><strong>Telepon</strong><small>Nomor telepon belum tersedia</small></span></span><?php endif; ?>
        </div>
    </section>
</div>
