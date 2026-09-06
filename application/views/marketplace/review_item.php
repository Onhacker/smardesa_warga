<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$review = isset($review) && is_array($review) ? $review : array();
$rating = max(0, min(5, (int) ($review['rating'] ?? 0)));
$reviewer = trim((string) ($review['reviewer_name'] ?? 'Warga')) ?: 'Warga';
$comment = trim((string) ($review['comment'] ?? ''));
$createdAt = trim((string) ($review['created_at'] ?? ''));
$dateLabel = $createdAt !== '' ? date('d M Y', strtotime($createdAt)) : '';
?>
<article class="market-review-item" data-review-id="<?= e($review['id'] ?? '') ?>">
    <div class="market-review-item-head">
        <div>
            <strong><?= e($reviewer) ?></strong>
            <span class="market-rating-stars market-review-stars" aria-label="<?= e($rating . ' dari 5 bintang') ?>"><?php for ($star = 1; $star <= 5; $star++): ?><i class="fa fa-star <?= $star <= $rating ? 'is-filled' : 'is-empty' ?>" aria-hidden="true"></i><?php endfor; ?></span>
        </div>
        <?php if ($dateLabel !== ''): ?><time datetime="<?= e($createdAt) ?>"><?= e($dateLabel) ?></time><?php endif; ?>
    </div>
    <p><?= e($comment) ?></p>
</article>
