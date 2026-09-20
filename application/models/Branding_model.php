<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public branding shared by the central administration and every PWA tenant.
 * The fallback keeps older databases and demo installs usable until migration
 * 025 has been applied.
 */
class Branding_model extends CI_Model
{
    public function current()
    {
        $branding = array(
            'nama_sistem' => $this->fallback('WARGA_SYSTEM_NAME', 'PUBLIC_SYSTEM_NAME', 'SIDAPULIK'),
            'kepanjangan' => $this->fallback('WARGA_SYSTEM_EXPANSION', 'PUBLIC_SYSTEM_EXPANSION', ''),
            'tagline' => $this->fallback('WARGA_SYSTEM_TAGLINE', 'PUBLIC_SYSTEM_TAGLINE', 'Bersama Membangun Kampung Digital')
        );

        if (!isset($this->db) || !$this->db->table_exists('app_public_branding')) {
            return $this->normalise($branding);
        }

        $fields = array();
        foreach (array('nama_sistem', 'kepanjangan', 'tagline') as $field) {
            if ($this->db->field_exists($field, 'app_public_branding')) {
                $fields[] = $field;
            }
        }
        if (empty($fields)) return $this->normalise($branding);

        $row = $this->db
            ->select(implode(', ', $fields))
            ->where('id', 1)
            ->limit(1)
            ->get('app_public_branding')
            ->row_array();
        if (is_array($row)) {
            foreach ($fields as $field) {
                if (isset($row[$field]) && trim((string) $row[$field]) !== '') {
                    $branding[$field] = $row[$field];
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
            'nama_sistem' => $this->clean($branding['nama_sistem'] ?? 'SIDAPULIK', 100, 'SIDAPULIK'),
            'kepanjangan' => $this->clean($branding['kepanjangan'] ?? '', 180, ''),
            'tagline' => $this->clean($branding['tagline'] ?? '', 255, 'Bersama Membangun Kampung Digital')
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
}
