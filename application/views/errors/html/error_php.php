<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (ENVIRONMENT === 'development'): ?><div style="margin:12px;padding:12px;border:1px solid #dce3e8;font:13px/1.5 monospace"><strong>PHP <?= html_escape($severity) ?></strong><br><?= html_escape($message) ?><br><?= html_escape($filepath) ?>:<?= (int) $line ?></div><?php endif; ?>
