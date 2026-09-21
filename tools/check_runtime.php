<?php
/**
 * Read-only staging runtime check. Run this script with the PHP binary that
 * will serve the PWA before switching production from PHP 8.0.
 */

$minimum = 80300;
$strictEnv = FALSE;
$explicitEnvFile = '';
foreach ($argv as $argument) {
    if ($argument === '--strict-env') $strictEnv = TRUE;
    if (strpos($argument, '--env=') === 0) $explicitEnvFile = trim(substr($argument, 6));
    if (strpos($argument, '--minimum=') === 0) {
        $value = substr($argument, 10);
        if (preg_match('/^8\\.[0-9](?:\\.\\d+)?$/', $value)) {
            $parts = array_map('intval', explode('.', $value));
            $minimum = ($parts[0] * 10000) + ($parts[1] * 100) + ($parts[2] ?? 0);
        }
    }
}

// Read only the allowlisted keys needed by this checker. Secrets unrelated to
// runtime readiness (DB password, VAPID private key, etc.) never enter this
// process. An explicit --env path avoids accidentally checking another app.
$projectRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$envFile = $explicitEnvFile !== '' ? $explicitEnvFile : $projectRoot . DIRECTORY_SEPARATOR . '.env';
$envReal = is_readable($envFile) ? realpath($envFile) : FALSE;
$publicRoot = $envReal !== FALSE ? dirname($envReal) : $projectRoot;
$envLoadError = '';
$envKeys = array('APP_ENV', 'APP_URL', 'APP_KEY', 'PRIVATE_STORAGE_PATH', 'WARGA_DEMO_MODE',
    'WARGA_CENTRAL_API_URL', 'WARGA_SESSION_SAVE_PATH', 'WARGA_CSP_ENFORCE',
    'WARGA_TRUSTED_DEVICE_TTL', 'WARGA_WEBAUTHN_RP_ID', 'WARGA_WEBAUTHN_RP_NAME',
    'WARGA_WEBAUTHN_ANDROID_PACKAGE', 'WARGA_WEBAUTHN_ANDROID_KEY_HASHES');
$envAllowlist = array_fill_keys($envKeys, TRUE);
if ($explicitEnvFile !== '') {
    // --env means "validate this file", not a mixture of this file and stale
    // variables inherited from the operator's shell.
    foreach ($envKeys as $key) putenv($key);
}
if ($explicitEnvFile !== '' && !is_readable($envFile)) {
    $envLoadError = 'File .env yang dipilih tidak dapat dibaca.';
} elseif (is_readable($envFile)) {
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        $envLoadError = 'File .env yang dipilih belum dapat dibaca.';
    } else {
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line[0] === '#' || !preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) continue;
            $key = $matches[1];
            if (!isset($envAllowlist[$key]) || ($explicitEnvFile === '' && getenv($key) !== FALSE && trim((string) getenv($key)) !== '')) continue;
            $value = trim($matches[2]);
            if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }
            putenv($key . '=' . $value);
        }
    }
}

$errors = 0;
$versionOk = PHP_VERSION_ID >= $minimum;
$minimumLabel = sprintf('%d.%d.%d', intdiv($minimum, 10000), intdiv($minimum % 10000, 100), $minimum % 100);
printf("PHP %s (minimum %s): %s\n", PHP_VERSION, $minimumLabel, $versionOk ? 'OK' : 'FAIL');
if (!$versionOk) $errors++;

$extensions = array('mysqli', 'curl', 'mbstring', 'openssl', 'zip', 'gd', 'intl', 'xml', 'dom', 'fileinfo', 'sodium');
foreach ($extensions as $extension) {
    $loaded = extension_loaded($extension);
    printf("ext %-10s %s\n", $extension . ':', $loaded ? 'OK' : 'FAIL');
    if (!$loaded) $errors++;
}
$gdFunctions = array('getimagesize', 'imagecreatetruecolor', 'imagecreatefromjpeg', 'imagecreatefrompng',
    'imagecreatefromwebp', 'imagecopyresampled', 'imagewebp');
foreach ($gdFunctions as $function) {
    $available = function_exists($function);
    printf("GD %-12s %s\n", $function . ':', $available ? 'OK' : 'FAIL');
    if (!$available) $errors++;
}

function runtime_absolute_path($path)
{
    return is_string($path) && ($path !== '') && ($path[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:[\\\\\/]/', $path));
}

function runtime_outside_root($path, $root)
{
    $real = realpath($path);
    $rootReal = realpath($root);
    if ($real === FALSE || $rootReal === FALSE) return FALSE;
    $candidate = rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $rootPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return strpos($candidate, $rootPrefix) !== 0;
}

if ($envLoadError !== '') {
    printf("env file:      %s\n", $strictEnv ? 'FAIL' : 'WARN');
    if ($strictEnv) $errors++;
}

$appEnv = trim((string) getenv('APP_ENV'));
$appUrl = trim((string) getenv('APP_URL'));
$appKey = trim((string) getenv('APP_KEY'));
$storage = trim((string) getenv('PRIVATE_STORAGE_PATH'));
$demoMode = trim((string) getenv('WARGA_DEMO_MODE'));
$centralUrl = trim((string) getenv('WARGA_CENTRAL_API_URL'));
$sessionPath = trim((string) getenv('WARGA_SESSION_SAVE_PATH'));
$cspEnforce = trim((string) getenv('WARGA_CSP_ENFORCE'));
$trustedTtlRaw = trim((string) getenv('WARGA_TRUSTED_DEVICE_TTL'));
$trustedTtl = $trustedTtlRaw === '' ? 31536000 : filter_var($trustedTtlRaw, FILTER_VALIDATE_INT);
$webauthnRpId = strtolower(trim((string) getenv('WARGA_WEBAUTHN_RP_ID')));
$webauthnRpName = trim((string) getenv('WARGA_WEBAUTHN_RP_NAME'));
$androidPackage = trim((string) getenv('WARGA_WEBAUTHN_ANDROID_PACKAGE')) ?: 'id.co.mediaverse.smartkampung';
$androidHashes = trim((string) getenv('WARGA_WEBAUTHN_ANDROID_KEY_HASHES'));
$appParts = parse_url($appUrl);
$centralParts = parse_url($centralUrl);
$rpIdOk = $webauthnRpId === '' || $webauthnRpId === 'localhost'
    || (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $webauthnRpId);
$rpNameOk = $webauthnRpName === '' || (function_exists('mb_strlen') ? mb_strlen($webauthnRpName, 'UTF-8') : strlen($webauthnRpName)) <= 120;
$packageOk = $androidPackage === '' || (bool) preg_match('/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)+$/', $androidPackage);
$hashesOk = TRUE;
if ($androidHashes !== '') {
    foreach (preg_split('/[,\s]+/', $androidHashes) as $hash) {
        if ($hash !== '' && !preg_match('/^[A-Za-z0-9_-]{20,100}$/', $hash)) {
            $hashesOk = FALSE;
            break;
        }
    }
}
$checks = array(
    'APP_ENV:' => $appEnv === 'production',
    'APP_URL:' => is_array($appParts) && strtolower((string) ($appParts['scheme'] ?? '')) === 'https' && !empty($appParts['host']),
    'APP_KEY:' => strlen($appKey) >= 32 && stripos($appKey, 'replace-with') === FALSE && stripos($appKey, 'ganti-dengan') === FALSE && stripos($appKey, 'change-before') === FALSE,
    'PRIVATE_STORAGE_PATH:' => runtime_absolute_path($storage) && is_dir($storage) && is_readable($storage) && is_writable($storage) && runtime_outside_root($storage, $publicRoot),
    'WARGA_DEMO_MODE:' => $demoMode === '0',
    'WARGA_CENTRAL_API_URL:' => is_array($centralParts) && strtolower((string) ($centralParts['scheme'] ?? '')) === 'https'
        && !empty($centralParts['host']) && rtrim((string) ($centralParts['path'] ?? ''), '/') === '/v1',
    'WARGA_SESSION_SAVE_PATH:' => $sessionPath !== '' && runtime_absolute_path($sessionPath) && is_dir($sessionPath)
        && is_readable($sessionPath) && is_writable($sessionPath) && runtime_outside_root($sessionPath, $publicRoot),
    'WARGA_CSP_ENFORCE:' => in_array($cspEnforce, array('0', '1'), TRUE),
    'WARGA_TRUSTED_DEVICE_TTL:' => $trustedTtl !== FALSE && $trustedTtl >= 86400 && $trustedTtl <= 31536000,
    'WARGA_WEBAUTHN_RP_ID:' => $rpIdOk,
    'WARGA_WEBAUTHN_RP_NAME:' => $rpNameOk,
    'WARGA_WEBAUTHN_ANDROID_PACKAGE:' => $packageOk,
    'WARGA_WEBAUTHN_ANDROID_KEY_HASHES:' => $hashesOk
);
foreach ($checks as $label => $ok) {
    printf("env %-26s %s\n", $label, $ok ? 'OK' : ($strictEnv ? 'FAIL' : 'WARN'));
    if (!$ok && $strictEnv) $errors++;
}

// Passkey support is intentionally checked here, rather than discovered on a
// user request. This catches a partial rsync (new PHP code without Composer's
// dependency) before the feature is exposed in the account screen.
$autoloadPath = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$passkeyLibraryOk = is_readable($autoloadPath);
if ($passkeyLibraryOk) {
    require_once $autoloadPath;
    $passkeyLibraryOk = class_exists('lbuchs\\WebAuthn\\WebAuthn');
}
printf("Passkey Composer library: %s\n", $passkeyLibraryOk ? 'OK' : ($strictEnv ? 'FAIL' : 'WARN'));
if (!$passkeyLibraryOk && $strictEnv) $errors++;

// A TWA deployment must publish the same asset-link relation used to derive
// Android APK key hashes. Browser-only/local deployments may omit this file.
$assetlinksPath = $publicRoot . DIRECTORY_SEPARATOR . '.well-known' . DIRECTORY_SEPARATOR . 'assetlinks.json';
$assetlinksOk = TRUE;
if ($appEnv === 'production' && $androidPackage !== '') {
    $assetlinksOk = is_readable($assetlinksPath) && filesize($assetlinksPath) <= 65536;
    if ($assetlinksOk) {
        $assetlinksData = json_decode((string) file_get_contents($assetlinksPath), TRUE);
        $assetlinksOk = is_array($assetlinksData);
        $relationFound = FALSE;
        if ($assetlinksOk) foreach ($assetlinksData as $statement) {
            $target = is_array($statement['target'] ?? NULL) ? $statement['target'] : array();
            $relations = is_array($statement['relation'] ?? NULL) ? $statement['relation'] : array();
            if (($target['namespace'] ?? '') === 'android_app'
                && ($target['package_name'] ?? '') === $androidPackage
                && in_array('delegate_permission/common.get_login_creds', $relations, TRUE)) {
                $relationFound = TRUE;
                break;
            }
        }
        $assetlinksOk = $assetlinksOk && $relationFound;
    }
}
printf("assetlinks Passkey relation: %s\n", $assetlinksOk ? 'OK' : ($strictEnv ? 'FAIL' : 'WARN'));
if (!$assetlinksOk && $strictEnv) $errors++;

if ($errors > 0) {
    fwrite(STDERR, "Runtime belum memenuhi syarat staging.\n");
    exit(1);
}
echo "Runtime siap untuk pengujian staging.\n";
