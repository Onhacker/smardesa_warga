<?php
if (PHP_SAPI !== 'cli') exit(1);
define('BASEPATH', dirname(__DIR__, 2) . '/system/');
define('APPPATH', dirname(__DIR__, 2) . '/application/');
define('ENVIRONMENT', 'testing');
function warga_demo_mode() { return FALSE; }
function warga_database_available() { return TRUE; }
function log_message($level, $message) {}
function is_php($version) { return version_compare(PHP_VERSION, $version, '>='); }
function show_error($message) { throw new RuntimeException(is_array($message) ? implode(' ', $message) : $message); }
class CI_Model { public $db; }
require BASEPATH . 'database/DB.php';
require APPPATH . 'models/Auth_model.php';

function check($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    echo "PASS $label\n";
}
function sql_batch($db, $sql)
{
    if (!$db->multi_query($sql)) throw new RuntimeException($db->error);
    do {
        if ($result = $db->store_result()) $result->free();
        if (!$db->more_results()) break;
        if (!$db->next_result()) throw new RuntimeException($db->error);
    } while (TRUE);
}

$socket = getenv('SMARTDESA_TEST_DB_SOCKET') ?: NULL;
$host = $socket ? 'localhost' : (getenv('SMARTDESA_TEST_DB_HOST') ?: '127.0.0.1');
$user = getenv('SMARTDESA_TEST_DB_USER') ?: 'root';
$password = getenv('SMARTDESA_TEST_DB_PASS') ?: '';
$port = (int) (getenv('SMARTDESA_TEST_DB_PORT') ?: 3306);
$admin = new mysqli($host, $user, $password, '', $port, $socket);
if ($admin->connect_errno) { fwrite(STDERR, "Test database unavailable: " . $admin->connect_errno . "\n"); exit(1); }
$database = 'sdw_pwa_test_' . bin2hex(random_bytes(8));
$created = FALSE;
$db = NULL;
$exitCode = 0;
try {
    check($admin->query("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"), 'temporary database created');
    $created = TRUE;
    $admin->select_db($database);
    sql_batch($admin, file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql'));
    $db = DB(array('hostname' => $socket ?: $host, 'username' => $user, 'password' => $password,
        'database' => $database, 'dbdriver' => 'mysqli', 'port' => $port, 'pconnect' => FALSE,
        'db_debug' => FALSE, 'char_set' => 'utf8mb4', 'dbcollat' => 'utf8mb4_unicode_ci'), TRUE);
    $village = '11111111-1111-4111-8111-111111111111';
    $other = '22222222-2222-4222-8222-222222222222';
    foreach (array($village, $other) as $i => $id) $db->insert('village_tenants', array('id' => $id,
        'province_code' => '95', 'province_name' => 'Test', 'regency_code' => '95.01', 'regency_name' => 'Test',
        'district_code' => '95.01.01', 'district_name' => 'Test', 'village_code' => 'test-' . $i, 'name' => 'Test ' . $i));
    $db->insert('roles', array('id' => 1, 'name' => 'Warga', 'slug' => 'warga'));
    $db->insert('users', array('id' => 1, 'role_id' => 1, 'village_id' => $village, 'name' => 'Warga Test', 'username' => 'warga-test', 'password_hash' => 'not-a-login'));
    $key = 'aaaaaaaaaaaaaaaaaaaaaaaa:1';
    $db->insert('citizen_profiles', array('id' => '33333333-3333-4333-8333-333333333333', 'user_id' => 1,
        'village_id' => $village, 'local_citizen_key' => $key, 'verification_status' => 'verified'));
    $db->insert('village_resident_directory', array('village_id' => $village, 'local_citizen_key' => $key,
        'nik_hash' => str_repeat('1', 64), 'kk_hash' => str_repeat('2', 64), 'name_hash' => str_repeat('3', 64),
        'display_name' => 'Warga Test', 'snapshot_id' => str_repeat('4', 64), 'status' => 'active'));
    $model = new Auth_model();
    $model->db = $db;
    check($model->citizen_is_verified(1, $village), 'verified active resident may submit');
    check(!$model->citizen_is_verified(1, $other), 'resident cannot submit for another village');
    $db->where(array('village_id' => $village, 'local_citizen_key' => $key))->update('village_resident_directory', array('status' => 'inactive'));
    check(!$model->citizen_is_verified(1, $village), 'inactive resident cannot submit');
    $db->where(array('village_id' => $village, 'local_citizen_key' => $key))->update('village_resident_directory', array('status' => 'active'));
    $db->where('user_id', 1)->update('citizen_profiles', array('verification_status' => 'revalidation_required'));
    check(!$model->citizen_is_verified(1, $village), 'identity change requires revalidation');
    $db->where('user_id', 1)->update('citizen_profiles', array('verification_status' => 'verified'));
    $db->where('id', 1)->update('users', array('is_active' => 0));
    check(!$model->citizen_is_verified(1, $village), 'disabled account cannot submit');
    echo "OK: resident access checks passed.\n";
} catch (Throwable $e) {
    $databaseError = $db ? $db->error() : array();
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n"
        . ($db ? $db->last_query() . "\n" : '')
        . (!empty($databaseError['message']) ? $databaseError['message'] . "\n" : '')
        . $e->getTraceAsString() . "\n");
    $exitCode = 1;
} finally {
    if ($db) $db->close();
    if ($created) $admin->query("DROP DATABASE `$database`");
    $admin->close();
}
exit($exitCode);
