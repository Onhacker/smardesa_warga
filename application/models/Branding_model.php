<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public branding shared by the central administration and every PWA tenant.
 * The fallback keeps older databases and demo installs usable until migration
 * 025 has been applied.
 */
class Branding_model extends CI_Model
{
    public function current($tenantCode = '')
    {
        $tenantCode = warga_normalize_tenant_code(
            $tenantCode !== '' ? $tenantCode : warga_tenant_code('default'),
            'default'
        );
        $institutionEnv = $this->fallback('WARGA_INSTITUTION_LABEL', 'PUBLIC_INSTITUTION_LABEL', '');
        $districtEnv = $this->fallback('WARGA_DISTRICT_LABEL', 'PUBLIC_DISTRICT_LABEL', '');
        $branding = array(
            'tenant_code' => $tenantCode,
            'tenant_name' => '',
            'nama_sistem' => $this->fallback('WARGA_SYSTEM_NAME', 'PUBLIC_SYSTEM_NAME', 'SIDAPULIK'),
            'kepanjangan' => $this->fallback('WARGA_SYSTEM_EXPANSION', 'PUBLIC_SYSTEM_EXPANSION', ''),
            'tagline' => $this->fallback('WARGA_SYSTEM_TAGLINE', 'PUBLIC_SYSTEM_TAGLINE', 'Bersama Membangun Kampung Digital'),
            'bentuk_lembaga' => $institutionEnv !== '' ? $institutionEnv : 'Desa',
            'bentuk_kecamatan' => $districtEnv !== '' ? $districtEnv : 'Kecamatan',
            'region_labels_managed' => ($institutionEnv !== '' || $districtEnv !== '') ? 1 : 0
        );

        // CodeIgniter exposes the database through CI_Model's magic getter;
        // isset($this->db) therefore reports false even when the connection is ready.
        $db = $this->db;
        if (!is_object($db) || !$db->table_exists('app_public_branding')) {
            return $this->normalise($branding);
        }

        $fields = array();
        foreach (array('tenant_code', 'tenant_name', 'nama_sistem', 'kepanjangan', 'tagline', 'bentuk_lembaga', 'bentuk_kecamatan', 'region_labels_managed') as $field) {
            if ($db->field_exists($field, 'app_public_branding')) {
                $fields[] = $field;
            }
        }
        if (empty($fields)) return $this->normalise($branding);

        $db->select(implode(', ', $fields));
        if ($db->field_exists('tenant_code', 'app_public_branding')) {
            $row = $db->where('tenant_code', $tenantCode)->limit(1)->get('app_public_branding')->row_array();
            if (!$row && $tenantCode !== 'default') {
                $db->reset_query();
                $row = $db->select(implode(', ', $fields))->where('tenant_code', 'default')->limit(1)->get('app_public_branding')->row_array();
            }
        } else {
            $row = $db->where('id', 1)->limit(1)->get('app_public_branding')->row_array();
        }
        if (is_array($row)) {
            foreach ($fields as $field) {
                if (isset($row[$field]) && trim((string) $row[$field]) !== '') {
                    $branding[$field] = $field === 'region_labels_managed' ? (int) $row[$field] : $row[$field];
                }
            }
        }

        return $this->normalise($branding);
    }

    private function fallback($primary, $secondary, $default)
    {
        foreach (array($primary, $secondary) as $key) {
            $value = getenv($key);
            if ($value !== false && trim((string) $value) !== '') return (string) $value;
        }
        return $default;
    }

    private function normalise(array $branding)
    {
        return array(
            'tenant_code' => warga_normalize_tenant_code($branding['tenant_code'] ?? 'default', 'default'),
            'tenant_name' => $this->clean($branding['tenant_name'] ?? '', 120, ''),
            'nama_sistem' => $this->clean($branding['nama_sistem'] ?? 'SIDAPULIK', 100, 'SIDAPULIK'),
            'kepanjangan' => $this->clean($branding['kepanjangan'] ?? '', 180, ''),
            'tagline' => $this->clean($branding['tagline'] ?? '', 255, 'Bersama Membangun Kampung Digital'),
            'bentuk_lembaga' => $this->label($branding['bentuk_lembaga'] ?? '', 'Desa'),
            'bentuk_kecamatan' => $this->label($branding['bentuk_kecamatan'] ?? '', 'Kecamatan'),
            'region_labels_managed' => !empty($branding['region_labels_managed']) ? 1 : 0
        );
    }

    private function clean($value, $length, $fallback)
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, (int) $length, 'UTF-8');
        } else {
            $value = substr($value, 0, (int) $length);
        }
        return trim((string) $value) !== '' ? trim((string) $value) : $fallback;
    }

    private function label($value, $fallback)
    {
        $value = $this->clean($value, 100, $fallback);
        return function_exists('mb_convert_case')
            ? mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
            : ucwords(strtolower($value));
    }
}
