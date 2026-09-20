<?php
// Standalone regression checks for the 20-item Surat catalogue. Run with
// `php tools/tests/service_catalog_pagination.php` (no database required).
define('BASEPATH', dirname(__DIR__, 2));
class CI_Model {}
require BASEPATH . '/application/models/Request_model.php';

class Test_service_catalog_model extends Request_model
{
    public function service_types($villageId = '')
    {
        $rows = array();
        for ($index = 1; $index <= 45; $index++) {
            $rows[] = array(
                'slug' => 'layanan-' . $index,
                'name' => 'Surat Layanan ' . $index,
                'short_name' => 'Layanan ' . $index,
                'description' => $index === 33 ? 'Untuk nelayan kampung' : 'Administrasi warga'
            );
        }
        return $rows;
    }
}

function check_catalog($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$catalog = new Test_service_catalog_model();
$first = $catalog->paginated_service_types('desa', array('page' => '1'));
$last = $catalog->paginated_service_types('desa', array('page' => '3'));
$matched = $catalog->paginated_service_types('desa', array('q' => 'nelayan surat'));
$empty = $catalog->paginated_service_types('desa', array('q' => 'tidak ditemukan', 'page' => '99'));

check_catalog(count($first['items']) === 20 && $first['total'] === 45 && $first['pages'] === 3, 'Halaman pertama tidak berisi 20 surat.');
check_catalog(count($last['items']) === 5 && $last['from'] === 41 && $last['to'] === 45, 'Halaman terakhir tidak sesuai.');
check_catalog($matched['total'] === 1 && $matched['items'][0]['slug'] === 'layanan-33', 'Pencarian multi-kata tidak sesuai.');
check_catalog($empty['total'] === 0 && $empty['page'] === 1 && $empty['pages'] === 1, 'Pencarian kosong tidak sesuai.');

echo "Pagination katalog Surat: OK\n";
