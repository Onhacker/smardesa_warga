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
