<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $items = isset($items) && is_array($items) ? $items : array(); ?>
<?php if (!$items): ?>
<div class="community-v22-empty-card" role="status" data-complaint-empty>
    <span class="community-v22-empty-icon is-complaint" aria-hidden="true"><i class="fa fa-comments"></i></span>
    <span class="community-v22-empty-copy">
        <strong>Belum ada pengaduan</strong>
        <span>Pengaduan warga akan tampil di sini setelah dikirim.</span>
    </span>
</div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <?php $this->load->view('community/complaint_item', array('item' => $item, 'canManage' => !empty($canManage))); ?>
    <?php endforeach; ?>
<?php endif; ?>
