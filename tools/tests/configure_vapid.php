<?php
if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$root = sys_get_temp_dir() . '/sdw-vapid-test-' . bin2hex(random_bytes(6));
mkdir($root . '/public_html', 0700, true);
$env = $root . '/public_html/.env';
$backup = $root . '/private/backups';
$script = dirname(__DIR__) . '/configure-vapid.php';
$count = 0;
function check($condition, $label) {
    global $count;
    if (!$condition) throw new RuntimeException('FAIL ' . $label);
    echo 'PASS ' . $label . "\n";
    $count++;
}
function run_setup(array $override = array()) {
    global $env, $backup, $script;
    $options = array_merge(array('env'=>$env, 'backup-dir'=>$backup, 'subject'=>'mailto:admin@example.test'), $override);
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
    foreach ($options as $name=>$value) $command .= ' ' . escapeshellarg('--' . $name . '=' . $value);
    exec($command . ' 2>&1', $output, $status);
    return array('output'=>implode("\n", $output), 'status'=>$status);
}
$exit = 0;
try {
    $original = "# Preserve existing settings\nAPP_KEY=test-only-app-key\nDB_PASS='test # value=123'\nDB_NAME=test_only\n";
    file_put_contents($env, $original);
    $result = run_setup();
    $installed = file_get_contents($env);
    $values = parse_ini_string($installed, false, INI_SCANNER_RAW);
    check($result['status'] === 0 && strlen($values['WARGA_VAPID_PUBLIC_KEY']) === 87
        && strlen($values['WARGA_VAPID_PRIVATE_KEY']) === 43, 'missing keys are generated and installed');
    check(strpos($installed, $original) === 0, 'unrelated environment settings remain untouched');
    check(strpos($result['output'], $values['WARGA_VAPID_PRIVATE_KEY']) === false, 'private key is not printed');
    $backups = glob($backup . '/*.backup');
    check(count($backups) === 1 && file_get_contents($backups[0]) === $original, 'original environment backed up privately');
    clearstatcache();
    check((fileperms($env) & 0777) === 0600 && (fileperms($backups[0]) & 0777) === 0600, 'environment and backup permissions are 600');
    check(run_setup()['status'] === 0 && file_get_contents($env) === $installed
        && count(glob($backup . '/*.backup')) === 1, 'repeated setup never rotates existing keys');

    $pairLines = 'WARGA_VAPID_PUBLIC_KEY=' . $values['WARGA_VAPID_PUBLIC_KEY'] . "\n"
        . 'WARGA_VAPID_PRIVATE_KEY=' . $values['WARGA_VAPID_PRIVATE_KEY'] . "\n";
    file_put_contents($env, $original . $pairLines);
    check(run_setup()['status'] === 0 && strpos(file_get_contents($env), $pairLines) !== false,
        'missing subject is filled without replacing keys');
    $partial = $original . 'WARGA_VAPID_PUBLIC_KEY=' . $values['WARGA_VAPID_PUBLIC_KEY'] . "\n";
    file_put_contents($env, $partial);
    check(run_setup()['status'] === 1 && file_get_contents($env) === $partial, 'partial key pair refuses automatic rotation');
    $placeholder = $original . "WARGA_VAPID_PUBLIC_KEY=replace-with-vapid-public-key\nWARGA_VAPID_PRIVATE_KEY=replace-with-vapid-private-key\nWARGA_VAPID_SUBJECT=mailto:admin@example.com\n";
    file_put_contents($env, $placeholder);
    check(run_setup()['status'] === 0 && strpos(file_get_contents($env), 'replace-with-') === false,
        'template placeholders are replaced');
    $duplicate = $installed . "WARGA_VAPID_PUBLIC_KEY=conflicting-key\n";
    file_put_contents($env, $duplicate);
    check(run_setup()['status'] === 1 && file_get_contents($env) === $duplicate, 'conflicting duplicates fail without writes');
    $invalid = $original . "WARGA_VAPID_PUBLIC_KEY=bad-key\nWARGA_VAPID_PRIVATE_KEY=bad-key\n";
    file_put_contents($env, $invalid);
    check(run_setup()['status'] === 1 && file_get_contents($env) === $invalid, 'invalid existing keys are not overwritten');
    file_put_contents($env, $original);
    check(run_setup(array('subject'=>"mailto:admin@example.test\nAPP_KEY=oops"))['status'] === 1
        && file_get_contents($env) === $original, 'subject cannot inject environment settings');
    check(run_setup(array('backup-dir'=>$root . '/public_html/backups'))['status'] === 1
        && file_get_contents($env) === $original, 'public backup destination rejected');
    mkdir($root . '/linked');
    symlink($env, $root . '/linked/.env');
    check(run_setup(array('env'=>$root . '/linked/.env'))['status'] === 1
        && file_get_contents($env) === $original, 'symlink target rejected');
    check(run_setup(array('env'=>$root . '/missing/.env'))['status'] === 1,
        'missing environment is not created accidentally');
    echo "OK: " . $count . " VAPID setup checks passed.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    $exit = 1;
} finally {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        if ($item->isDir() && !$item->isLink()) rmdir($item->getPathname());
        else unlink($item->getPathname());
    }
    rmdir($root);
}
exit($exit);
