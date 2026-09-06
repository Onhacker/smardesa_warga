<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$isAuthenticated = !empty($isAuthenticated);
?>
<div class="market-review-modal" data-market-review-modal hidden>
    <button type="button" class="market-review-backdrop" data-market-review-close aria-label="Tutup rating"></button>
    <section class="market-review-dialog" role="dialog" aria-modal="true" aria-labelledby="market-review-title">
        <div class="market-review-dialog-head">
            <div>
                <p class="market-eyebrow market-eyebrow-blue">ULASAN PRODUK</p>
                <h2 id="market-review-title" data-market-review-title>Beri rating</h2>
            </div>
            <button type="button" class="market-review-close" data-market-review-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </div>
        <form data-market-review-form data-review-authenticated="<?= $isAuthenticated ? '1' : '0' ?>">
            <?php if (!$isAuthenticated): ?>
                <div class="market-review-login-notice" data-market-review-login-notice role="alert">
                    <span class="market-review-login-notice-icon" aria-hidden="true"><i class="fa fa-lock"></i></span>
                    <div class="market-review-login-notice-copy">
                        <strong>Login diperlukan.</strong>
                        <p>Silakan masuk terlebih dahulu untuk memberi rating.</p>
                        <a href="<?= e(site_url('login')) ?>" class="market-review-login-link bg-red-dark"><span>Masuk sekarang</span><i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
            <?php endif; ?>
            <p class="market-review-product-label" data-market-review-product-label>Pilih rating untuk produk ini.</p>
            <div class="market-review-picker" role="radiogroup" aria-label="Pilih jumlah bintang">
                <?php for ($star = 1; $star <= 5; $star++): ?><button type="button" class="market-review-star-button" data-review-rating="<?= $star ?>" role="radio" aria-checked="false" aria-label="<?= $star ?> bintang"><i class="fa fa-star" aria-hidden="true"></i></button><?php endfor; ?>
            </div>
            <p class="market-review-rating-label" data-market-review-rating-label>Pilih bintang</p>
            <label class="market-review-comment-field">
                <span>Komentar <small>(opsional)</small></span>
                <textarea name="comment" rows="4" maxlength="1000" placeholder="Bagikan pengalaman Anda dengan produk ini (boleh dikosongkan)…" data-market-review-comment></textarea>
            </label>
            <p class="market-review-status" data-market-review-status role="status" aria-live="polite"></p>
            <div class="market-review-actions">
                <button type="button" class="market-review-cancel" data-market-review-close>Batal</button>
                <button type="submit" class="market-review-submit"><i class="fa fa-paper-plane color-white" aria-hidden="true"></i><span class="color-white">Kirim ulasan</span></button>
            </div>
        </form>
    </section>
</div>
