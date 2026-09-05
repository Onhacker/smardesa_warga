<?php
require dirname(__DIR__).'/vendor/autoload.php';
$keys=MinishlinkWebPushVAPID::createVapidKeys();
echo 'WARGA_VAPID_PUBLIC_KEY='.$keys['publicKey'].PHP_EOL;
echo 'WARGA_VAPID_PRIVATE_KEY='.$keys['privateKey'].PHP_EOL;
echo 'WARGA_VAPID_SUBJECT=mailto:admin@mediaverse.co.id'.PHP_EOL;
