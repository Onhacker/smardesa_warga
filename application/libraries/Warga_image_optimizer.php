<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Small, dependency-free image validation and optimisation service.
 *
 * User supplied images are decoded with GD, orientation-corrected when EXIF
 * is available, resized to a bounded canvas, and written as metadata-free
 * WebP. Upload paths reject a server without WebP support so an unoptimised
 * file is never stored accidentally in any environment.
 */
class Warga_image_optimizer
{
    /**
     * Keep the compressed-upload path safe on shared hosting. A 12 MP phone
     * photo is accepted, while very large camera originals are rejected
     * before GD allocates a full-size bitmap.
     */
    const MAX_INPUT_DIMENSION = 10000;
    const MAX_INPUT_PIXELS = 16000000;

    private $loaders = array(
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp'
    );

    public function validate($source, $mime, $maxDimension = self::MAX_INPUT_DIMENSION, $maxPixels = self::MAX_INPUT_PIXELS)
    {
        $source = (string) $source;
        $mime = strtolower(trim((string) $mime));
        if ($source === '' || !is_file($source) || !is_readable($source)) {
            return array('success' => FALSE, 'message' => 'Berkas gambar tidak dapat dibaca.');
        }
        if (!isset($this->loaders[$mime]) || !function_exists('getimagesize')) {
            return array('success' => FALSE, 'message' => 'Pemeriksaan gambar belum tersedia pada server.');
        }
        $info = @getimagesize($source);
        if (!is_array($info) || empty($info[0]) || empty($info[1])) {
            return array('success' => FALSE, 'message' => 'Isi gambar tidak valid atau rusak.');
        }
        $width = (int) $info[0];
        $height = (int) $info[1];
        $detectedMime = strtolower(trim((string) ($info['mime'] ?? '')));
        if ($detectedMime !== '' && $detectedMime !== $mime) {
            return array('success' => FALSE, 'message' => 'Jenis isi gambar tidak sesuai dengan berkasnya.');
        }
        $pixels = (float) $width * (float) $height;
        if ($width < 1 || $height < 1 || $width > (int) $maxDimension || $height > (int) $maxDimension
            || $pixels > (float) $maxPixels) {
            $pixelLabel = number_format(((int) $maxPixels) / 1000000, 0, ',', '.');
            return array('success' => FALSE, 'message' => 'Ukuran gambar terlalu besar. Gunakan gambar maksimal ' . number_format((int) $maxDimension, 0, ',', '.') . ' px dan ' . $pixelLabel . ' megapiksel.');
        }
        if (!$this->decode_budget_ok($width, $height)) {
            return array('success' => FALSE, 'message' => 'Gambar terlalu besar untuk kapasitas server saat ini. Gunakan foto dengan resolusi lebih kecil.');
        }
        return array('success' => TRUE, 'width' => $width, 'height' => $height, 'mime' => $mime);
    }

    /**
     * Check the approximate GD allocation before imagecreatefrom*(). GD may
     * allocate outside PHP's normal string buffers, so a conservative budget
     * prevents a large camera image from taking down a shared PHP worker.
     */
    public function decode_budget_ok($width, $height)
    {
        $width = (int) $width;
        $height = (int) $height;
        if ($width < 1 || $height < 1) return FALSE;
        $limit = $this->memory_limit_bytes();
        if ($limit === NULL) return TRUE;
        $used = function_exists('memory_get_usage') ? (int) @memory_get_usage(TRUE) : 0;
        $available = max(0, $limit - $used);
        // Source bitmap, EXIF rotation and the resized canvas can coexist.
        $estimated = ((float) $width * (float) $height * 6.0) + (16 * 1024 * 1024);
        return $estimated <= ($available * 0.80);
    }

    /**
     * Return an optimisation result or a small reason code for a safe caller
     * fallback. The destination is written atomically so interrupted uploads
     * never leave a partial file that can later be streamed.
     */
    public function optimize($source, $mime, $destination, $maxDimension = 1600, $quality = 84)
    {
        $mime = strtolower(trim((string) $mime));
        if (!isset($this->loaders[$mime]) || !function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) {
            return array('success' => FALSE, 'code' => 'unsupported');
        }
        $loader = $this->loaders[$mime];
        if (!function_exists($loader)) return array('success' => FALSE, 'code' => 'unsupported');
        $dimensions = @getimagesize((string) $source);
        if (is_array($dimensions) && !$this->decode_budget_ok((int) ($dimensions[0] ?? 0), (int) ($dimensions[1] ?? 0))) {
            return array('success' => FALSE, 'code' => 'memory');
        }
        $image = @call_user_func($loader, (string) $source);
        if (!$image) return array('success' => FALSE, 'code' => 'invalid');

        // Resize the decoded source before EXIF rotation. Rotating a 12–16 MP
        // bitmap can temporarily double its memory use; rotating the bounded
        // 1600 px canvas preserves the same visual orientation much more safely.
        $resized = $this->resize_canvas($image, max(1, (int) $maxDimension));
        $this->destroy($image);
        if ($resized === NULL) {
            return array('success' => FALSE, 'code' => 'invalid');
        }
        $oriented = $this->apply_orientation($resized, $source, $mime);
        if ($oriented === NULL) {
            $this->destroy($resized);
            return array('success' => FALSE, 'code' => 'invalid');
        }
        if ($oriented !== $resized) $this->destroy($resized);
        $resized = $oriented;

        $directory = dirname((string) $destination);
        if (!is_dir($directory) && !@mkdir($directory, 0750, TRUE) && !is_dir($directory)) {
            $this->destroy($resized);
            return array('success' => FALSE, 'code' => 'storage');
        }
        $temporary = @tempnam($directory, '.sdw-image-');
        if (!$temporary || !@imagewebp($resized, $temporary, max(1, min(100, (int) $quality)))
            || !is_file($temporary) || (int) @filesize($temporary) < 1) {
            if ($temporary && is_file($temporary)) @unlink($temporary);
            $this->destroy($resized);
            return array('success' => FALSE, 'code' => 'encode');
        }
        $outputWidth = (int) @imagesx($resized);
        $outputHeight = (int) @imagesy($resized);
        $this->destroy($resized);
        @chmod($temporary, 0640);
        if (is_file((string) $destination)) @unlink((string) $destination);
        if (!@rename($temporary, (string) $destination)) {
            @unlink($temporary);
            return array('success' => FALSE, 'code' => 'storage');
        }
        @chmod((string) $destination, 0640);
        return array(
            'success' => TRUE,
            'mime' => 'image/webp',
            'file_size' => (int) @filesize((string) $destination),
            'width' => $outputWidth,
            'height' => $outputHeight
        );
    }

    private function apply_orientation($image, $source, $mime)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) return $image;
        $exif = @exif_read_data((string) $source);
        $orientation = is_array($exif) && isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
        switch ($orientation) {
            case 2: if (function_exists('imageflip')) @imageflip($image, IMG_FLIP_HORIZONTAL); break;
            case 3: $image = @imagerotate($image, 180, 0); break;
            case 4: if (function_exists('imageflip')) @imageflip($image, IMG_FLIP_VERTICAL); break;
            case 5: if (function_exists('imageflip')) @imageflip($image, IMG_FLIP_HORIZONTAL); $image = @imagerotate($image, -90, 0); break;
            case 6: $image = @imagerotate($image, -90, 0); break;
            case 7: if (function_exists('imageflip')) @imageflip($image, IMG_FLIP_HORIZONTAL); $image = @imagerotate($image, 90, 0); break;
            case 8: $image = @imagerotate($image, 90, 0); break;
        }
        return $image ?: NULL;
    }

    private function resize_canvas($image, $maxDimension)
    {
        $width = max(1, (int) @imagesx($image));
        $height = max(1, (int) @imagesy($image));
        $scale = min(1, (float) $maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $canvas = @imagecreatetruecolor($newWidth, $newHeight);
        if (!$canvas) return NULL;
        @imagealphablending($canvas, FALSE);
        @imagesavealpha($canvas, TRUE);
        $transparent = @imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== FALSE) @imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        if (!@imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
            $this->destroy($canvas);
            return NULL;
        }
        @imagealphablending($canvas, TRUE);
        @imagesavealpha($canvas, TRUE);
        return $canvas;
    }

    private function destroy($image)
    {
        if (is_object($image) || is_resource($image)) @imagedestroy($image);
    }

    private function memory_limit_bytes()
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') return NULL;
        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([kmgt]?)$/i', $value, $matches)) return NULL;
        $bytes = (float) $matches[1];
        $unit = strtolower($matches[2] ?? '');
        $multipliers = array('k' => 1024, 'm' => 1048576, 'g' => 1073741824, 't' => 1099511627776);
        if (isset($multipliers[$unit])) $bytes *= $multipliers[$unit];
        return $bytes > 0 ? (int) min($bytes, PHP_INT_MAX) : NULL;
    }
}
