<?php
if (PHP_SAPI !== 'cli') exit(1);

define('BASEPATH', dirname(__DIR__, 2) . '/system/');

// Auth_model only needs the CodeIgniter base class at declaration time. This
// small stub keeps the checks independent from a database and HTTP bootstrap.
if (!class_exists('CI_Model', FALSE)) {
    class CI_Model
    {
        public $config;
    }
}

require dirname(__DIR__, 2) . '/application/helpers/warga_helper.php';
require dirname(__DIR__, 2) . '/application/libraries/Warga_image_optimizer.php';
require dirname(__DIR__, 2) . '/application/models/Auth_model.php';

function hardening_check($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    echo "PASS {$label}\n";
}

function hardening_private_call($object, $method, array $arguments)
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(TRUE);
    return $reflection->invokeArgs($object, $arguments);
}

// Role checks must default to deny. In particular, an arbitrary non-warga
// slug must not accidentally inherit staff access.
$allowedRoles = warga_allowed_roles();
hardening_check($allowedRoles === array(
    'warga', 'sekdes', 'kepala-desa', 'admin-desa', 'admin-kabupaten', 'admin-pusat'
), 'allowlist role hanya berisi role PWA yang disetujui');
hardening_check(warga_role_is_allowed(' warga '), 'role valid menerima spasi tepi');
hardening_check(!warga_role_is_allowed('super-admin'), 'role asing ditolak');
hardening_check(!warga_role_is_allowed('KEPALA-DESA'), 'slug role bersifat eksplisit');
hardening_check(!warga_is_staff(array('role_slug' => 'warga')), 'warga bukan petugas');
hardening_check(warga_is_staff(array('role_slug' => 'sekdes')), 'sekdes dikenali sebagai petugas');
hardening_check(!warga_is_staff(array('role_slug' => 'owner')), 'role non-warga asing tidak menjadi petugas');
hardening_check(!warga_is_staff(array()), 'role kosong tidak menjadi petugas');

// Registration throttling hashes formatting variants of one identity to the
// same key, while still separating distinct villages and contacts.
$auth = new Auth_model();
$auth->config = new class {
    public function item($key)
    {
        return $key === 'encryption_key' ? 'test-only-encryption-key' : NULL;
    }
};
$phoneA = hardening_private_call($auth, 'registration_identity_hash', array(
    '+62 812-3456-7890', '91.01-0203 0405-0001', '  village-01 '
));
$phoneB = hardening_private_call($auth, 'registration_identity_hash', array(
    '+6281234567890', '9101020304050001', 'VILLAGE-01'
));
$emailA = hardening_private_call($auth, 'registration_identity_hash', array(
    'WARGA@EXAMPLE.COM', '9101020304050001', 'VILLAGE-01'
));
$emailB = hardening_private_call($auth, 'registration_identity_hash', array(
    'warga@example.com', '9101020304050001', 'VILLAGE-01'
));
$otherVillage = hardening_private_call($auth, 'registration_identity_hash', array(
    '+6281234567890', '9101020304050001', 'VILLAGE-02'
));
hardening_check(hash_equals($phoneA, $phoneB), 'format nomor dan NIK tidak dapat melewati throttle');
hardening_check(hash_equals($emailA, $emailB), 'huruf besar email tidak dapat melewati throttle');
hardening_check(!hash_equals($phoneA, $otherVillage), 'identitas desa berbeda tetap dipisahkan');

// Exercise the image guards without allocating a large bitmap. Custom caps
// let the fixture prove both dimension and megapixel rejection paths.
if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
    fwrite(STDERR, "FAIL GD dengan dukungan PNG wajib tersedia.\n");
    exit(1);
}

$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sidapulik-hardening-' . bin2hex(random_bytes(6));
if (!mkdir($directory, 0700, TRUE) && !is_dir($directory)) {
    throw new RuntimeException('Folder uji hardening belum dapat dibuat.');
}
$source = $directory . DIRECTORY_SEPARATOR . 'limits.png';

try {
    $image = imagecreatetruecolor(120, 80);
    hardening_check($image !== FALSE, 'kanvas batas gambar dapat dibuat');
    $color = imagecolorallocate($image, 28, 91, 151);
    imagefilledrectangle($image, 0, 0, 119, 79, $color);
    hardening_check(imagepng($image, $source), 'fixture batas gambar dapat ditulis');
    imagedestroy($image);

    $optimizer = new Warga_image_optimizer();
    $valid = $optimizer->validate($source, 'image/png', 120, 9600);
    $dimensionRejected = $optimizer->validate($source, 'image/png', 119, 9600);
    $pixelsRejected = $optimizer->validate($source, 'image/png', 120, 9599);
    hardening_check(!empty($valid['success']), 'gambar tepat pada batas diterima');
    hardening_check(empty($dimensionRejected['success']), 'gambar melewati batas dimensi ditolak');
    hardening_check(empty($pixelsRejected['success']), 'gambar melewati batas megapiksel ditolak');
    $originalMemoryLimit = ini_get('memory_limit');
    $changedMemoryLimit = @ini_set('memory_limit', '64M');
    if ($changedMemoryLimit !== FALSE) {
        hardening_check(!$optimizer->decode_budget_ok(100000, 100000), 'perkiraan memori ekstrem ditolak sebelum decode');
        @ini_set('memory_limit', (string) $originalMemoryLimit);
    } else {
        echo "SKIP batas memory_limit tidak dapat diubah oleh runtime ini\n";
    }
} finally {
    if (is_file($source)) @unlink($source);
    @rmdir($directory);
}

echo "OK: security hardening checks passed.\n";
