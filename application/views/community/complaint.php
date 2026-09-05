<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community">
<article class="community-detail"><div class="community-meta"><span><?= e(warga_complaint_status($item['status'])) ?></span><time><?= e(tanggal_id($item['created_at'],true)) ?></time></div>
<h1><?= e($item['title']) ?></h1><p><?= e($item['citizen_name']) ?><?= $item['location'] ? ' | '.e($item['location']) : '' ?></p>
<div class="community-prose"><?= nl2br(e($item['body'])) ?></div></article>
<section class="community-replies"><h2>Tanggapan</h2>
<?php if (!$replies): ?><p class="community-empty">Belum ada tanggapan.</p><?php endif; ?>
<?php foreach ($replies as $reply): ?><article class="community-item"><div class="community-meta"><strong><?= e($reply['actor_name']) ?></strong><time><?= e(tanggal_id($reply['created_at'],true)) ?></time></div><p><?= nl2br(e($reply['message'])) ?></p><span class="community-status"><?= e(warga_complaint_status($reply['status'])) ?></span></article><?php endforeach; ?>
</section>
<?php if ($canManage): ?><form class="community-compose" method="post" action="<?= site_url('pengaduan/'.$item['id'].'/tanggapan') ?>"><?= csrf_field() ?>
<label for="complaint-status">Status</label><select id="complaint-status" name="status">
<?php foreach (array('received','processing','resolved','rejected') as $status): ?><option value="<?= $status ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e(warga_complaint_status($status)) ?></option><?php endforeach; ?></select>
<label for="reply-message">Tanggapan</label><textarea id="reply-message" name="message" rows="4" minlength="5" maxlength="3000" required></textarea>
<button class="community-button"><i class="fa fa-paper-plane"></i> Kirim Tanggapan</button></form><?php endif; ?>
</div>

