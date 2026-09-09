<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('warga_asset_url')) {
    /**
     * Return a cache-safe URL for a public static asset.
     *
     * The per-file modification time changes only when that asset is deployed,
     * so browsers may retain immutable responses without keeping an old build.
     */
    function warga_asset_url($path)
    {
        static $versions = array();
        $path = ltrim((string) $path, '/');
        if (!isset($versions[$path])) {
            $absolute = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $versions[$path] = is_file($absolute) ? (string) @filemtime($absolute) : '1';
        }
        $version = $versions[$path];
        return base_url($path) . '?v=' . rawurlencode($version !== '' ? $version : '1');
    }
}

if (!function_exists('e')) {
    function e($value) { return html_escape((string) $value); }
}

if (!function_exists('warga_demo_mode')) {
    function warga_demo_mode() { return getenv('WARGA_DEMO_MODE') === '1'; }
}

/**
 * Resolve the institution type from a tenant/village name when older tenant
 * records do not yet have an explicit bentuk_lembaga value.  This keeps
 * citizen-facing copy consistent without changing any workflow identifiers.
 */
if (!function_exists('warga_institution_label')) {
    function warga_institution_label($name, $fallback = 'Desa')
    {
        $name = trim((string) $name);
        if (preg_match('/^(desa|kampung|kelurahan|nagari|gampong)\b/iu', $name, $matches)) {
            return function_exists('mb_convert_case')
                ? mb_convert_case($matches[1], MB_CASE_TITLE, 'UTF-8')
                : ucfirst(strtolower($matches[1]));
        }
        $fallback = trim((string) $fallback);
        return $fallback !== '' ? $fallback : 'Desa';
    }
}

if (!function_exists('warga_replace_institution')) {
    function warga_replace_institution($text, $institution)
    {
        $institution = trim((string) $institution);
        if ($institution === '') return (string) $text;
        return preg_replace_callback('/\bdesa\b/iu', static function ($match) use ($institution) {
            $source = (string) $match[0];
            if ($source === strtoupper($source)) return strtoupper($institution);
            if ($source === strtolower($source)) {
                return function_exists('mb_strtolower') ? mb_strtolower($institution, 'UTF-8') : strtolower($institution);
            }
            return $institution;
        }, (string) $text);
    }
}

function warga_complaint_status($status)
{
    $labels = array('submitted'=>'Dikirim','received'=>'Diterima','processing'=>'Ditindaklanjuti','resolved'=>'Selesai','rejected'=>'Ditolak');
    return $labels[$status] ?? $status;
}

if (!function_exists('warga_database_available')) {
    function warga_database_available()
    {
        $CI =& get_instance();
        return isset($CI->db) && is_object($CI->db) && !empty($CI->db->conn_id);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        $CI =& get_instance();
        return '<input type="hidden" name="' . e($CI->security->get_csrf_token_name()) . '" value="' . e($CI->security->get_csrf_hash()) . '">';
    }
}

if (!function_exists('old')) {
    function old($key, $default = '')
    {
        $CI =& get_instance();
        $value = $CI->input->post($key);
        return $value !== NULL ? $value : $default;
    }
}

if (!function_exists('nav_is')) {
    function nav_is($controllers)
    {
        $CI =& get_instance();
        $controllers = is_array($controllers) ? $controllers : array($controllers);
        return in_array(strtolower($CI->router->fetch_class()), array_map('strtolower', $controllers), TRUE);
    }
}

if (!function_exists('tanggal_id')) {
    function tanggal_id($date, $withTime = FALSE)
    {
        if (!$date) return '-';
        $months = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
        $timestamp = strtotime($date);
        if (!$timestamp) return '-';
        $result = date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
        return $withTime ? $result . ' ' . date('H:i', $timestamp) : $result;
    }
}

if (!function_exists('warga_status_label')) {
    function warga_status_label($status)
    {
        $map = array(
            'draft' => array('bg-gray-dark', 'Draft'),
            'submitted' => array('bg-blue-dark', 'Diajukan'),
            'verified' => array('bg-teal-dark', 'Diverifikasi'),
            'revision' => array('bg-yellow-dark', 'Perlu Perbaikan'),
            'approved' => array('bg-green-dark', 'Disetujui'),
            'rejected' => array('bg-red-dark', 'Ditolak'),
            'issued' => array('bg-green-dark', 'Surat Terbit'),
            'syncing' => array('bg-orange-dark', 'Disinkronkan'),
            'synced' => array('bg-green-dark', 'Tersinkron')
        );
        $item = isset($map[$status]) ? $map[$status] : array('bg-gray-dark', ucwords(str_replace('_', ' ', $status)));
        return '<span class="badge ' . e($item[0]) . ' color-white warga-status-badge">' . e($item[1]) . '</span>';
    }
}

if (!function_exists('warga_status_text')) {
    function warga_status_text($status)
    {
        $map = array(
            'draft' => 'Draft',
            'submitted' => 'Diajukan',
            'verified' => 'Diverifikasi',
            'revision' => 'Perlu Perbaikan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'issued' => 'Surat Terbit',
            'syncing' => 'Disinkronkan',
            'synced' => 'Tersinkron'
        );
        return isset($map[$status]) ? $map[$status] : ucwords(str_replace('_', ' ', (string) $status));
    }
}

if (!function_exists('warga_is_staff')) {
    function warga_is_staff($user)
    {
        return is_array($user) && isset($user['role_slug']) && $user['role_slug'] !== 'warga';
    }
}

if (!function_exists('warga_home_route')) {
    function warga_home_route($user)
    {
        return warga_is_staff($user) ? 'petugas' : 'dashboard';
    }
}

if (!function_exists('warga_initials')) {
    function warga_initials($name)
    {
        $parts = preg_split('/\s+/u', trim((string) $name));
        $letters = '';
        foreach (array_slice($parts ?: array(), 0, 2) as $part) {
            if ($part !== '') $letters .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($part, 0, 1));
        }
        return $letters ?: 'W';
    }
}

if (!function_exists('warga_service_icon')) {
    /**
     * Match the icon rules used by SmartDesa's admin_surat catalogue.
     * short_name is included because it keeps the original Master Surat name
     * when the citizen-facing title has been customised.
     */
    function warga_service_icon(array $service)
    {
        $slug = isset($service['slug']) ? (string) $service['slug'] : '';
        $name = isset($service['name']) ? (string) $service['name'] : '';
        $shortName = isset($service['short_name']) ? (string) $service['short_name'] : '';
        $haystack = trim($slug . ' ' . $name . ' ' . $shortName);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
        $rules = array(
            array(array('pendamping pasien'), 'fa fa-hands-helping', 'surat-icon-rose'),
            array(array('verifikasi data kesejahteraan', 'dtks', 'siks-ng'), 'fa fa-database', 'surat-icon-teal'),
            array(array('persetujuan kepala suku', 'pemilik hak ulayat', 'kepala suku hak ulayat'), 'fa fa-flag', 'surat-icon-amber'),
            array(array('pelepasan hak tanah adat', 'tanah adat/ulayat', 'tanah adat ulayat'), 'fa fa-map-marked-alt', 'surat-icon-amber'),
            array(array('perubahan elemen data kependudukan', 'f-1.05', 'f105'), 'fa fa-file-signature', 'surat-icon-blue'),
            array(array('penebusan pupuk', 'pupuk bersubsidi'), 'fa fa-seedling', 'surat-icon-green'),
            array(array('kesepakatan batas tanah adat'), 'fa fa-map-marked-alt', 'surat-icon-teal'),
            array(array('pernyataan batas tanah', 'tetangga sempadan'), 'fa fa-draw-polygon', 'surat-icon-teal'),
            array(array('tanah garapan'), 'fa fa-seedling', 'surat-icon-green'),
            array(array('penguasaan fisik bidang tanah', 'sppfbt'), 'fa fa-map-marked-alt', 'surat-icon-amber'),
            array(array('kartu identitas anak', 'penerbitan kia'), 'fa fa-id-card', 'surat-icon-cyan'),
            array(array('penerbitan ktp-el', 'penerbitan ktp el'), 'fa fa-id-card', 'surat-icon-blue'),
            array(array('lansia', 'lanjut usia'), 'fa fa-user-friends', 'surat-icon-green'),
            array(array('disabilitas', 'difabel'), 'fa fa-wheelchair', 'surat-icon-teal'),
            array(array('janda', 'duda'), 'fa fa-heart-broken', 'surat-icon-purple'),
            array(array('masih hidup'), 'fa fa-heartbeat', 'surat-icon-green'),
            array(array('yatim', 'piatu'), 'fa fa-user-friends', 'surat-icon-rose'),
            array(array('kelahiran', 'lahir'), 'fa fa-baby-carriage', 'surat-icon-cyan'),
            array(array('penguburan', 'kubur'), 'fa fa-cross', 'surat-icon-slate'),
            array(array('kematian', 'mati'), 'fa fa-ribbon', 'surat-icon-slate'),
            array(array('pindah-datang', 'pindah datang'), 'fa fa-home', 'surat-icon-green'),
            array(array('pindah'), 'fa fa-truck', 'surat-icon-orange'),
            array(array('kartu keluarga'), 'fa fa-users', 'surat-icon-blue'),
            array(array('ktp'), 'fa fa-id-card', 'surat-icon-blue'),
            array(array('paspor'), 'fa fa-passport', 'surat-icon-blue'),
            array(array('catatan kriminal', 'skck'), 'fa fa-shield-alt', 'surat-icon-indigo'),
            array(array('kehilangan'), 'fa fa-exclamation-triangle', 'surat-icon-red'),
            array(array('bantuan sosial lainnya', 'blt dana desa', 'blt-dana-desa'), 'fa fa-money-bill-wave', 'surat-icon-green'),
            array(array('rekomendasi bantuan sosial', 'bantuan sosial tunai', 'bst', 'bansos'), 'fa fa-money-bill-wave', 'surat-icon-green'),
            array(array('tidak mampu', 'jamkesos'), 'fa fa-hands-helping', 'surat-icon-rose'),
            array(array('beda nama'), 'fa fa-file-alt', 'surat-icon-purple'),
            array(array('ahli waris'), 'fa fa-users', 'surat-icon-purple'),
            array(array('wali'), 'fa fa-user-shield', 'surat-icon-indigo'),
            array(array('pencatatan perkawinan non-muslim', 'perkawinan non-muslim'), 'fa fa-hands-helping', 'surat-icon-pink'),
            array(array('paket surat nikah', 'surat-nikah-n1-n5', 'n1-n5'), 'fa fa-heart', 'surat-icon-pink'),
            array(array('pengantar nikah'), 'fa fa-heart', 'surat-icon-pink'),
            array(array('pencatatan isbat', 'isbat'), 'fa fa-certificate', 'surat-icon-purple'),
            array(array('calon pengantin', 'pengantin-n4'), 'fa fa-heart', 'surat-icon-pink'),
            array(array('perjanjian damai'), 'fa fa-hands-helping', 'surat-icon-green'),
            array(array('pbb'), 'fa fa-receipt', 'surat-icon-green'),
            array(array('tidak sengketa'), 'fa fa-shield-alt', 'surat-icon-green'),
            array(array('sporadik', 'riwayat tanah', 'pencocokan data'), 'fa fa-map-marker-alt', 'surat-icon-amber'),
            array(array('beda luas'), 'fa fa-ruler-combined', 'surat-icon-amber'),
            array(array('kepemilikan rumah/tanah', 'kepemilikan rumah / tanah'), 'fa fa-home', 'surat-icon-green'),
            array(array('harga tanah'), 'fa fa-home', 'surat-icon-green'),
            array(array('hubungan keluarga'), 'fa fa-users', 'surat-icon-purple'),
            array(array('gudang'), 'fa fa-warehouse', 'surat-icon-slate'),
            array(array('kendaraan'), 'fa fa-car', 'surat-icon-blue'),
            array(array('penduduk sementara', 'sktps'), 'fa fa-id-card', 'surat-icon-teal'),
            array(array('hewan ternak', 'ternak'), 'fa fa-paw', 'surat-icon-amber'),
            array(array('pengantar barang'), 'fa fa-box', 'surat-icon-orange'),
            array(array('bbm', 'solar'), 'fa fa-gas-pump', 'surat-icon-orange'),
            array(array('surat umum'), 'fa fa-file-signature', 'surat-icon-blue'),
            array(array('belum memiliki rumah'), 'fa fa-home', 'surat-icon-orange'),
            array(array('rumah tidak layak huni', 'rtlh', 'rehabilitasi rumah', 'bedah rumah'), 'fa fa-home', 'surat-icon-amber'),
            array(array('korban bencana', 'kebakaran rumah', 'banjir bandang', 'puting beliung'), 'fa fa-cloud-showers-heavy', 'surat-icon-red'),
            array(array('belum bekerja'), 'fa fa-briefcase', 'surat-icon-amber'),
            array(array('belum menikah'), 'fa fa-user-friends', 'surat-icon-pink'),
            array(array('beasiswa'), 'fa fa-school', 'surat-icon-blue'),
            array(array('penduduk aktif sekolah', 'aktif sekolah'), 'fa fa-school', 'surat-icon-blue'),
            array(array('penghasilan'), 'fa fa-money-bill-wave', 'surat-icon-green'),
            array(array('usaha', 'domisili usaha'), 'fa fa-store', 'surat-icon-teal'),
            array(array('jual beli'), 'fa fa-exchange-alt', 'surat-icon-purple'),
            array(array('keramaian'), 'fa fa-bullhorn', 'surat-icon-orange'),
            array(array('domisili'), 'fa fa-map-marker-alt', 'surat-icon-blue')
        );
        foreach ($rules as $rule) {
            foreach ($rule[0] as $needle) {
                if (strpos($haystack, $needle) !== FALSE) return array('icon' => $rule[1], 'class' => $rule[2]);
            }
        }
        return array('icon' => 'fa fa-file-alt', 'class' => 'surat-icon-neutral');
    }
}

if (!function_exists('warga_request_service_icon')) {
    /**
     * Adapt a stored request/notification row to the same icon catalogue used
     * by the service dashboard and the Semua Jenis Surat page.
     */
    function warga_request_service_icon(array $request)
    {
        return warga_service_icon(array(
            'slug' => isset($request['service_slug']) ? (string) $request['service_slug'] : '',
            'name' => isset($request['service_name']) ? (string) $request['service_name'] : '',
            'short_name' => isset($request['service_short_name']) ? (string) $request['service_short_name'] : ''
        ));
    }
}

if (!function_exists('warga_uuid')) {
    function warga_uuid()
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}

if (!function_exists('warga_json')) {
    function warga_json($value)
    {
        return e(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
    }
}

if (!function_exists('warga_request_schema')) {
    /**
     * Return only the small, display-safe part of a request form schema.
     * The schema is supplied by a village administrator, so views must never
     * assume that every field or option has the expected shape.
     */
    function warga_request_schema(array $request)
    {
        $schema = isset($request['form_schema']) && is_array($request['form_schema']) ? $request['form_schema'] : array();
        $fields = isset($schema['fields']) && is_array($schema['fields']) ? $schema['fields'] : array();
        $safe = array();
        foreach ($fields as $field) {
            if (!is_array($field)) continue;
            $key = trim((string) (isset($field['key']) ? $field['key'] : ''));
            $label = trim((string) (isset($field['label']) ? $field['label'] : ''));
            $type = strtolower(trim((string) (isset($field['type']) ? $field['type'] : 'text')));
            if ($key === '' || $label === '' || !preg_match('/^[a-z][a-z0-9_]{0,49}$/', $key)) continue;
            if (!in_array($type, array('text', 'textarea', 'date', 'select', 'number', 'tel', 'email', 'file'), TRUE)) continue;
            $options = array();
            if (isset($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $option) {
                    if (is_array($option)) {
                        $value = isset($option['value']) && is_scalar($option['value']) ? (string) $option['value'] : '';
                        $optionLabel = isset($option['label']) && is_scalar($option['label']) ? (string) $option['label'] : $value;
                    } elseif (is_scalar($option)) {
                        $value = (string) $option;
                        $optionLabel = $value;
                    } else {
                        continue;
                    }
                    if ($value !== '') $options[$value] = $optionLabel !== '' ? $optionLabel : $value;
                }
            }
            $safe[] = array(
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'options' => $options
            );
        }
        return array('version' => max(1, (int) (isset($schema['version']) ? $schema['version'] : 1)), 'fields' => $safe);
    }
}

if (!function_exists('warga_request_form_rows')) {
    function warga_request_form_rows(array $request)
    {
        $schema = warga_request_schema($request);
        $values = isset($request['form_data']) && is_array($request['form_data']) ? $request['form_data'] : array();
        $rows = array();
        foreach ($schema['fields'] as $field) {
            if ($field['type'] === 'file') continue;
            $key = $field['key'];
            $value = array_key_exists($key, $values) ? $values[$key] : '';
            if (is_array($value)) {
                $parts = array();
                foreach ($value as $part) if (is_scalar($part)) $parts[] = trim((string) $part);
                $value = implode(', ', array_filter($parts, function ($part) { return $part !== ''; }));
            } elseif (is_scalar($value)) {
                $value = trim((string) $value);
            } else {
                $value = '';
            }
            if ($field['type'] === 'select' && $value !== '' && isset($field['options'][$value])) {
                $value = $field['options'][$value];
            }
            if ($field['type'] === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $formatted = tanggal_id($value);
                if ($formatted !== '-') $value = $formatted;
            }
            $rows[] = array('key' => $key, 'label' => $field['label'], 'value' => $value !== '' ? $value : '-');
        }
        return $rows;
    }
}

if (!function_exists('warga_request_file_labels')) {
    function warga_request_file_labels(array $request)
    {
        $schema = warga_request_schema($request);
        $labels = array();
        foreach ($schema['fields'] as $field) {
            if ($field['type'] === 'file') $labels[$field['key']] = $field['label'];
        }
        return $labels;
    }
}
