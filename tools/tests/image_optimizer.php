<?php
if (PHP_SAPI !== 'cli') exit(1);

define('BASEPATH', dirname(__DIR__, 2) . '/system/');
require dirname(__DIR__, 2) . '/application/libraries/Warga_image_optimizer.php';

function image_check($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    echo "PASS {$label}\n";
}

if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng') || !function_exists('imagewebp')) {
    fwrite(STDERR, "FAIL GD dengan dukungan PNG dan WebP wajib tersedia.\n");
    exit(1);
}

$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sidapulik-image-' . bin2hex(random_bytes(6));
if (!mkdir($directory, 0700, TRUE) && !is_dir($directory)) {
    throw new RuntimeException('Folder uji gambar belum dapat dibuat.');
}

$source = $directory . DIRECTORY_SEPARATOR . 'source.png';
$output = $directory . DIRECTORY_SEPARATOR . 'output.webp';
$invalid = $directory . DIRECTORY_SEPARATOR . 'invalid.png';

try {
    $image = imagecreatetruecolor(2400, 1800);
    image_check($image !== FALSE, 'kanvas uji dapat dibuat');
    $color = imagecolorallocate($image, 36, 89, 155);
    imagefilledrectangle($image, 0, 0, 2399, 1799, $color);
    image_check(imagepng($image, $source), 'gambar sumber dapat ditulis');
    imagedestroy($image);
    file_put_contents($invalid, '<?php echo "not an image";');

    $optimizer = new Warga_image_optimizer();
    $valid = $optimizer->validate($source, 'image/png');
    image_check(!empty($valid['success']), 'gambar valid lolos pemeriksaan isi');
    $mismatch = $optimizer->validate($source, 'image/jpeg');
    image_check(empty($mismatch['success']), 'MIME palsu ditolak');
    $bad = $optimizer->validate($invalid, 'image/png');
    image_check(empty($bad['success']), 'berkas non-gambar ditolak');

    $result = $optimizer->optimize($source, 'image/png', $output, 1600, 84);
    image_check(!empty($result['success']) && is_file($output), 'gambar dikompresi menjadi WebP');
    $info = getimagesize($output);
    image_check(is_array($info) && (int) $info[0] === 1600 && (int) $info[1] === 1200, 'sisi terpanjang dibatasi 1600 px');
    image_check(strtolower((string) ($info['mime'] ?? '')) === 'image/webp', 'hasil memiliki MIME WebP');
    image_check((int) filesize($output) > 0, 'hasil kompresi tidak kosong');
} finally {
    foreach (array($source, $output, $invalid) as $path) {
        if (is_file($path)) @unlink($path);
    }
    @rmdir($directory);
}

echo "OK: image optimizer checks passed.\n";
