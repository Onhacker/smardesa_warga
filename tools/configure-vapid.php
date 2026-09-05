<?php
// Run from the repository, never through the public website.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';

umask(0077);
$handle = null;
$temporary = null;
$exitCode = 0;
try {
    $options = getopt('', array('env:', 'backup-dir:', 'subject:'));
    $path = (string)($options['env'] ?? '');
    $backupDir = (string)($options['backup-dir'] ?? '');
    if ($path === '' || $backupDir === '' || $path[0] !== '/' || $backupDir[0] !== '/') {
        throw new RuntimeException('Gunakan --env=/path/.env dan --backup-dir=/path/privat di luar document root.');
    }
    if (is_link($path) || !is_file($path) || basename($path) !== '.env') {
        throw new RuntimeException('Target harus file .env yang sudah ada, bukan symlink.');
    }
    $path = realpath($path);
    $handle = fopen($path, 'rb');
    if (!$handle || !flock($handle, LOCK_EX)) throw new RuntimeException('Tidak dapat mengunci .env.');
    $original = stream_get_contents($handle);
    if ($original === false) throw new RuntimeException('Tidak dapat membaca .env.');

    $keys = array('WARGA_VAPID_PUBLIC_KEY', 'WARGA_VAPID_PRIVATE_KEY', 'WARGA_VAPID_SUBJECT');
    $values = array();
    $lines = explode("\n", $original);
    $kept = array();
    foreach ($lines as $line) {
        $parts = explode('=', trim($line), 2);
        $name = trim($parts[0]);
        if (count($parts) !== 2 || !in_array($name, $keys, true)) { $kept[] = $line; continue; }
        $value = trim($parts[1]);
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"')
            || ($value[0] === "'" && substr($value, -1) === "'"))) $value = substr($value, 1, -1);
        if (isset($values[$name]) && $values[$name] !== $value) {
            throw new RuntimeException('Ada pengaturan VAPID ganda yang berbeda. Rapikan entri ' . $name . ' terlebih dahulu.');
        }
        $values[$name] = $value;
    }
    $missing = static function ($value) { return $value === '' || strpos($value, 'replace-with-') === 0; };
    $public = $values[$keys[0]] ?? '';
    $private = $values[$keys[1]] ?? '';
    if ($missing($public) !== $missing($private)) {
        throw new RuntimeException('Hanya satu kunci VAPID tersimpan. Lengkapi pasangan aslinya; kunci tidak diganti otomatis.');
    }
    $generated = $missing($public);
    if ($generated) {
        $pair = \Minishlink\WebPush\VAPID::createVapidKeys();
        $public = $pair['publicKey'];
        $private = $pair['privateKey'];
    }
    $subject = $values[$keys[2]] ?? '';
    if ($subject === '' || $subject === 'mailto:admin@example.com') $subject = (string)($options['subject'] ?? '');
    $subjectParts = parse_url($subject);
    $validSubject = strpos($subject, 'mailto:') === 0
        ? filter_var(substr($subject, 7), FILTER_VALIDATE_EMAIL)
        : (filter_var($subject, FILTER_VALIDATE_URL) && ($subjectParts['scheme'] ?? '') === 'https'
            && !isset($subjectParts['user']) && !isset($subjectParts['pass']));
    if (!$validSubject || preg_match('/[\r\n]/', $subject)) {
        throw new RuntimeException('Isi --subject=mailto:alamat-email-pengelola atau URL HTTPS pengelola.');
    }
    try {
        \Minishlink\WebPush\VAPID::validate(array('publicKey'=>$public, 'privateKey'=>$private, 'subject'=>$subject));
    } catch (Throwable $e) {
        throw new RuntimeException('Format kunci VAPID tersimpan tidak valid. Pulihkan pasangan asli; tidak ada perubahan.');
    }
    if (!$generated && ($values[$keys[2]] ?? '') === $subject) {
        if (!chmod($path, 0600)) throw new RuntimeException('Tidak dapat mengamankan permission .env.');
        echo "Konfigurasi VAPID sudah lengkap. Kunci lama dipertahankan.\n";
        return;
    }

    $eol = strpos($original, "\r\n") !== false ? "\r\n" : "\n";
    $updated = rtrim(implode("\n", $kept), "\r\n") . $eol . $eol
        . $keys[0] . '=' . $public . $eol
        . $keys[1] . '=' . $private . $eol
        . $keys[2] . '=' . $subject . $eol;
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) throw new RuntimeException('Folder backup tidak dapat dibuat.');
    $backupDir = realpath($backupDir);
    $appDir = dirname($path);
    if (!$backupDir || $backupDir === $appDir || strpos($backupDir . '/', $appDir . '/') === 0) {
        throw new RuntimeException('Folder backup wajib di luar folder aplikasi/document root.');
    }
    if (stat($appDir)['dev'] !== stat($backupDir)['dev']) {
        throw new RuntimeException('Gunakan folder backup pada filesystem yang sama agar penggantian .env tetap atomik.');
    }
    $backup = $backupDir . '/warga-env-before-vapid-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.backup';
    $backupHandle = fopen($backup, 'xb');
    if (!$backupHandle) throw new RuntimeException('Tidak dapat membuat backup privat.');
    try {
        if (fwrite($backupHandle, $original) !== strlen($original) || !fflush($backupHandle)) {
            throw new RuntimeException('Backup belum lengkap; .env tidak diubah.');
        }
    } finally { fclose($backupHandle); }
    $temporary = tempnam($backupDir, '.vapid-');
    if ($temporary === false || file_put_contents($temporary, $updated) !== strlen($updated)
        || !chmod($temporary, 0600)) throw new RuntimeException('Tidak dapat menyiapkan konfigurasi privat.');
    if (is_link($path) || file_get_contents($path) !== $original) {
        throw new RuntimeException('.env berubah selama proses. Tidak ditimpa; jalankan ulang setelah perubahan lain selesai.');
    }
    if (!rename($temporary, $path)) throw new RuntimeException('Tidak dapat memasang .env. Backup privat tersedia.');
    $temporary = null;
    echo "Konfigurasi VAPID tersimpan. Pengaturan lainnya dipertahankan.\n";
    echo "Backup privat: " . $backup . "\n";
    echo "Kunci tidak ditampilkan. Jalankan worker dari public_html untuk menguji.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($temporary && is_file($temporary)) unlink($temporary);
    if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
}
exit($exitCode);
