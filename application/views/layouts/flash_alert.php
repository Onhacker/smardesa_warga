<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$flashType = isset($flashType) && $flashType === 'error' ? 'error' : 'success';
$flashTitle = isset($flashTitle) && trim((string) $flashTitle) !== ''
    ? (string) $flashTitle
    : ($flashType === 'error' ? 'Perlu diperbaiki' : 'Berhasil');
$flashIcon = $flashType === 'error' ? 'fa-exclamation' : 'fa-check';
?>
<div class="alert fade show warga-flash warga-flash--<?= e($flashType) ?>" role="<?= $flashType === 'error' ? 'alert' : 'status' ?>"<?= $flashType === 'success' ? ' aria-live="polite"' : '' ?>>
    <span class="warga-flash-icon" aria-hidden="true"><i class="fa <?= e($flashIcon) ?>"></i></span>
    <div class="warga-flash-copy">
        <strong><?= e($flashTitle) ?></strong>
        <div class="warga-flash-message"><?= !empty($flashAllowHtml) ? $flashMessage : e($flashMessage) ?></div>
    </div>
    <button type="button" class="warga-flash-close" aria-label="Tutup pemberitahuan"><i class="fa fa-times" aria-hidden="true"></i></button>
</div>
