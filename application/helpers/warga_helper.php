<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('e')) {
    function e($value) { return html_escape((string) $value); }
}

if (!function_exists('warga_demo_mode')) {
    function warga_demo_mode() { return getenv('WARGA_DEMO_MODE') === '1'; }
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
            array(array('pendamping pasien'), 'ti ti-heart-handshake', 'surat-icon-rose'),
            array(array('verifikasi data kesejahteraan', 'dtks', 'siks-ng'), 'ti ti-database-heart', 'surat-icon-teal'),
            array(array('persetujuan kepala suku', 'pemilik hak ulayat', 'kepala suku hak ulayat'), 'ti ti-flag-2', 'surat-icon-amber'),
            array(array('pelepasan hak tanah adat', 'tanah adat/ulayat', 'tanah adat ulayat'), 'ti ti-map-2', 'surat-icon-amber'),
            array(array('perubahan elemen data kependudukan', 'f-1.05', 'f105'), 'ti ti-file-pencil', 'surat-icon-blue'),
            array(array('penebusan pupuk', 'pupuk bersubsidi'), 'ti ti-plant-2', 'surat-icon-green'),
            array(array('kesepakatan batas tanah adat'), 'ti ti-map-pin-share', 'surat-icon-teal'),
            array(array('pernyataan batas tanah', 'tetangga sempadan'), 'ti ti-vector-bezier-2', 'surat-icon-teal'),
            array(array('tanah garapan'), 'ti ti-plant-2', 'surat-icon-green'),
            array(array('penguasaan fisik bidang tanah', 'sppfbt'), 'ti ti-map-2', 'surat-icon-amber'),
            array(array('kartu identitas anak', 'penerbitan kia'), 'ti ti-id-badge-2', 'surat-icon-cyan'),
            array(array('penerbitan ktp-el', 'penerbitan ktp el'), 'ti ti-id', 'surat-icon-blue'),
            array(array('lansia', 'lanjut usia'), 'ti ti-user-heart', 'surat-icon-green'),
            array(array('disabilitas', 'difabel'), 'ti ti-accessible', 'surat-icon-teal'),
            array(array('janda', 'duda'), 'ti ti-heart-broken', 'surat-icon-purple'),
            array(array('masih hidup'), 'ti ti-heartbeat', 'surat-icon-green'),
            array(array('yatim', 'piatu'), 'ti ti-user-heart', 'surat-icon-rose'),
            array(array('kelahiran', 'lahir'), 'ti ti-baby-carriage', 'surat-icon-cyan'),
            array(array('penguburan', 'kubur'), 'ti ti-cross', 'surat-icon-slate'),
            array(array('kematian', 'mati'), 'ti ti-ribbon-health', 'surat-icon-slate'),
            array(array('pindah-datang', 'pindah datang'), 'ti ti-home-plus', 'surat-icon-green'),
            array(array('pindah'), 'ti ti-truck-delivery', 'surat-icon-orange'),
            array(array('kartu keluarga'), 'ti ti-users-group', 'surat-icon-blue'),
            array(array('ktp'), 'ti ti-id', 'surat-icon-blue'),
            array(array('paspor'), 'ti ti-plane', 'surat-icon-blue'),
            array(array('catatan kriminal', 'skck'), 'ti ti-shield-check', 'surat-icon-indigo'),
            array(array('kehilangan'), 'ti ti-alert-triangle', 'surat-icon-red'),
            array(array('bantuan sosial lainnya', 'blt dana desa', 'blt-dana-desa'), 'ti ti-cash-banknote', 'surat-icon-green'),
            array(array('rekomendasi bantuan sosial', 'bantuan sosial tunai', 'bst', 'bansos'), 'ti ti-cash', 'surat-icon-green'),
            array(array('tidak mampu', 'jamkesos'), 'ti ti-heart-handshake', 'surat-icon-rose'),
            array(array('beda nama'), 'ti ti-file-diff', 'surat-icon-purple'),
            array(array('ahli waris'), 'ti ti-users-group', 'surat-icon-purple'),
            array(array('wali'), 'ti ti-user-shield', 'surat-icon-indigo'),
            array(array('pencatatan perkawinan non-muslim', 'perkawinan non-muslim'), 'ti ti-heart-handshake', 'surat-icon-pink'),
            array(array('paket surat nikah', 'surat-nikah-n1-n5', 'n1-n5'), 'ti ti-hearts', 'surat-icon-pink'),
            array(array('pengantar nikah'), 'ti ti-heart', 'surat-icon-pink'),
            array(array('pencatatan isbat', 'isbat'), 'ti ti-certificate', 'surat-icon-purple'),
            array(array('calon pengantin', 'pengantin-n4'), 'ti ti-hearts', 'surat-icon-pink'),
            array(array('perjanjian damai'), 'ti ti-heart-handshake', 'surat-icon-green'),
            array(array('pbb'), 'ti ti-receipt-tax', 'surat-icon-green'),
            array(array('tidak sengketa'), 'ti ti-shield-check', 'surat-icon-green'),
            array(array('sporadik', 'riwayat tanah', 'pencocokan data'), 'ti ti-map-pin', 'surat-icon-amber'),
            array(array('beda luas'), 'ti ti-ruler-measure', 'surat-icon-amber'),
            array(array('kepemilikan rumah/tanah', 'kepemilikan rumah / tanah'), 'ti ti-home-check', 'surat-icon-green'),
            array(array('harga tanah'), 'ti ti-home-dollar', 'surat-icon-green'),
            array(array('hubungan keluarga'), 'ti ti-users-group', 'surat-icon-purple'),
            array(array('gudang'), 'ti ti-building-warehouse', 'surat-icon-slate'),
            array(array('kendaraan'), 'ti ti-car', 'surat-icon-blue'),
            array(array('penduduk sementara', 'sktps'), 'ti ti-id-badge', 'surat-icon-teal'),
            array(array('hewan ternak', 'ternak'), 'ti ti-horse-toy', 'surat-icon-amber'),
            array(array('pengantar barang'), 'ti ti-package', 'surat-icon-orange'),
            array(array('bbm', 'solar'), 'ti ti-gas-station', 'surat-icon-orange'),
            array(array('surat umum'), 'ti ti-file-pencil', 'surat-icon-blue'),
            array(array('belum memiliki rumah'), 'ti ti-home-off', 'surat-icon-orange'),
            array(array('rumah tidak layak huni', 'rtlh', 'rehabilitasi rumah', 'bedah rumah'), 'ti ti-home', 'surat-icon-amber'),
            array(array('korban bencana', 'kebakaran rumah', 'banjir bandang', 'puting beliung'), 'ti ti-cloud-storm', 'surat-icon-red'),
            array(array('belum bekerja'), 'ti ti-briefcase-off', 'surat-icon-amber'),
            array(array('belum menikah'), 'ti ti-user-heart', 'surat-icon-pink'),
            array(array('beasiswa'), 'ti ti-school', 'surat-icon-blue'),
            array(array('penduduk aktif sekolah', 'aktif sekolah'), 'ti ti-school', 'surat-icon-blue'),
            array(array('penghasilan'), 'ti ti-cash-banknote', 'surat-icon-green'),
            array(array('usaha', 'domisili usaha'), 'ti ti-building-store', 'surat-icon-teal'),
            array(array('jual beli'), 'ti ti-arrows-exchange', 'surat-icon-purple'),
            array(array('keramaian'), 'ti ti-speakerphone', 'surat-icon-orange'),
            array(array('domisili'), 'ti ti-map-pin', 'surat-icon-blue')
        );
        foreach ($rules as $rule) {
            foreach ($rule[0] as $needle) {
                if (strpos($haystack, $needle) !== FALSE) return array('icon' => $rule[1], 'class' => $rule[2]);
            }
        }
        return array('icon' => 'ti ti-file-description', 'class' => 'surat-icon-neutral');
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
