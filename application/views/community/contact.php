<?php defined('BASEPATH') OR exit('No direct script access allowed'); $contact=$village['contact']; ?>
<div class="warga-community">
<header class="community-heading"><div><h1>Kontak <?= e($village['institution']) ?></h1><p><?= e($village['name'] ?? $currentUser['village_name']) ?></p></div><i class="fa fa-address-book"></i></header>
<dl class="community-contact">
<?php foreach (array('address'=>'Alamat kantor','phone'=>'Telepon','email'=>'Email','website'=>'Website','office_hours'=>'Jam pelayanan') as $key=>$label): ?>
<div><dt><?= e($label) ?></dt><dd>
<?php $value=trim((string)($contact[$key] ?? '')); ?>
<?php if ($key==='phone' && preg_match('/^\+?[0-9 ()-]{8,25}$/D',$value)): ?><a href="tel:<?= e(preg_replace('/[^+0-9]/','',$value)) ?>"><?= e($value) ?></a>
<?php elseif ($key==='email' && filter_var($value,FILTER_VALIDATE_EMAIL)): ?><a href="mailto:<?= e($value) ?>"><?= e($value) ?></a>
<?php else: ?><?= e($value !== '' ? $value : 'Belum tersedia') ?><?php endif; ?>
</dd></div><?php endforeach; ?>
<div><dt>Distrik / Kecamatan</dt><dd><?= e($village['district_name'] ?? '-') ?></dd></div>
<div><dt>Kabupaten</dt><dd><?= e($village['regency_name'] ?? '-') ?></dd></div>
</dl></div>

