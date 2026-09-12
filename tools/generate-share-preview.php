<?php
// Generate the public 1200x630 Open Graph image from the installed PWA icon.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('gd')) { fwrite(STDERR, "Ekstensi GD diperlukan.\n"); exit(1); }

$root = dirname(__DIR__);
$source = $root . '/assets/pwa/icon-512.png';
$target = $root . '/assets/pwa/share-preview.png';
$regularFonts = array(
    '/System/Library/Fonts/Supplemental/Arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
);
$boldFonts = array(
    '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
    '/usr/share/fonts/truetype/msttcorefonts/Arial_Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
);
$findFont = static function (array $candidates) {
    foreach ($candidates as $candidate) if (is_readable($candidate)) return $candidate;
    return '';
};
$regular = $findFont($regularFonts);
$bold = $findFont($boldFonts);
if (!is_readable($source) || $regular === '' || $bold === '') {
    fwrite(STDERR, "Ikon sumber atau font tidak tersedia.\n");
    exit(1);
}

$canvas = imagecreatetruecolor(1200, 630);
imagealphablending($canvas, TRUE);
for ($y = 0; $y < 630; $y++) {
    $ratio = $y / 629;
    $red = (int) round(13 + (34 - 13) * $ratio);
    $green = (int) round(45 + (104 - 45) * $ratio);
    $blue = (int) round(100 + (181 - 100) * $ratio);
    imageline($canvas, 0, $y, 1200, $y, imagecolorallocate($canvas, $red, $green, $blue));
}

$whiteSoft = imagecolorallocatealpha($canvas, 255, 255, 255, 111);
$blueSoft = imagecolorallocatealpha($canvas, 75, 176, 255, 102);
imagefilledellipse($canvas, 1070, 48, 410, 410, $whiteSoft);
imagefilledellipse($canvas, 1120, 610, 560, 330, $blueSoft);
imagefilledellipse($canvas, 35, 625, 510, 245, imagecolorallocatealpha($canvas, 12, 40, 91, 70));

$roundedRect = static function ($image, $x1, $y1, $x2, $y2, $radius, $color) {
    imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
};

$roundedRect($canvas, 66, 111, 468, 513, 52, imagecolorallocatealpha($canvas, 3, 20, 54, 83));
$roundedRect($canvas, 76, 101, 458, 483, 48, imagecolorallocate($canvas, 255, 255, 255));
$icon = imagecreatefrompng($source);
imagecopyresampled($canvas, $icon, 97, 122, 0, 0, 340, 340, imagesx($icon), imagesy($icon));
imagedestroy($icon);

$white = imagecolorallocate($canvas, 255, 255, 255);
$sky = imagecolorallocate($canvas, 158, 218, 255);
$navy = imagecolorallocate($canvas, 20, 68, 128);
imagettftext($canvas, 22, 0, 535, 154, $sky, $bold, 'SI DAPULIK');
imagettftext($canvas, 48, 0, 531, 230, $white, $bold, 'Layanan Digital');
imagettftext($canvas, 48, 0, 531, 290, $white, $bold, 'untuk Warga');
imagettftext($canvas, 21, 0, 535, 344, $white, $regular, 'Mudah diakses, aman, dan selalu terhubung.');

$roundedRect($canvas, 531, 389, 1113, 449, 18, imagecolorallocatealpha($canvas, 255, 255, 255, 10));
imagettftext($canvas, 17, 0, 558, 427, $navy, $bold, 'SURAT  •  PENGADUAN  •  PASAR DIGITAL');
imagettftext($canvas, 16, 0, 535, 505, $sky, $regular, 'warga-smartdesa.mediaverse.co.id');

imagesavealpha($canvas, TRUE);
if (!imagepng($canvas, $target, 8)) {
    fwrite(STDERR, "Gagal menulis gambar preview.\n");
    imagedestroy($canvas);
    exit(1);
}
imagedestroy($canvas);
echo $target . PHP_EOL;
