<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Request_model extends CI_Model
{
    private $catalog_schema_ready = false;

    private function institution_for_context(array $context = array())
    {
        $this->load->model('Community_model');
        $village = $this->Community_model->village(
            $context['village_id'] ?? '',
            $context['village_name'] ?? ''
        );
        return trim((string) ($village['institution'] ?? 'Desa')) ?: 'Desa';
    }

    private function institution_lower(array $context = array())
    {
        $label = $this->institution_for_context($context);
        return function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
    }

    private function normalize_row(array $row)
    {
        $payloadSchema = NULL;
        if (isset($row['payload_json'])) {
            $payload = json_decode((string) $row['payload_json'], TRUE);
            if (is_array($payload)) {
                $row['purpose'] = isset($payload['purpose']) ? $payload['purpose'] : '';
                $row['note'] = isset($payload['note']) ? $payload['note'] : '';
                $row['form_data'] = isset($payload['form_data']) && is_array($payload['form_data']) ? $payload['form_data'] : array();
                if (isset($payload['form_schema_version'])) {
                    $row['form_schema_version'] = (int) $payload['form_schema_version'];
                }
                if (isset($payload['form_schema']) && is_array($payload['form_schema'])
                    && isset($payload['form_schema']['fields']) && is_array($payload['form_schema']['fields'])) {
                    $payloadSchema = $payload['form_schema'];
                }
            }
        }
        if (!isset($row['purpose'])) $row['purpose'] = '';
        if (!isset($row['note'])) $row['note'] = '';
        if (!isset($row['form_data'])) $row['form_data'] = array();
        if (!isset($row['form_schema_version'])) $row['form_schema_version'] = 0;
        // Keep historical requests readable after the current catalogue is
        // edited. The schema snapshot sent with the request wins.
        if ($payloadSchema !== NULL) {
            $row['form_schema'] = $payloadSchema;
        } elseif (isset($row['catalog_form_schema_json'])) {
            $catalogSchema = json_decode((string) $row['catalog_form_schema_json'], TRUE);
            if (is_array($catalogSchema) && isset($catalogSchema['fields']) && is_array($catalogSchema['fields'])) {
                $row['form_schema'] = $catalogSchema;
            }
        }
        if (!isset($row['form_schema']) || !is_array($row['form_schema'])) {
            $row['form_schema'] = array('version' => (int) $row['form_schema_version'] ?: 1, 'fields' => array());
        }
        if (!isset($row['catalog_template_key'])) $row['catalog_template_key'] = '';
        return $row;
    }

    private function ensure_catalog_schema()
    {
        // Database schema changes are performed by deployment migrations,
        // never during a user-facing request. Keep this guard only for
        // backwards-compatible call sites; it now has zero DB overhead.
        if ($this->catalog_schema_ready) return;
        $this->catalog_schema_ready = true;
    }

    private function private_storage_path()
    {
        $configured = trim((string) getenv('PRIVATE_STORAGE_PATH'));
        if (ENVIRONMENT === 'production' && $configured === '') return NULL;
        $path = $configured !== '' ? $configured : FCPATH . 'storage';
        if (!is_dir($path) && !@mkdir($path, 0750, TRUE) && !is_dir($path)) return NULL;
        $real = realpath($path);
        if ($real === FALSE || !is_dir($real) || !is_readable($real) || !is_writable($real)) return NULL;
        if (ENVIRONMENT === 'production') {
            $public = realpath(FCPATH);
            if ($public !== FALSE && strpos(rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, rtrim($public, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) return NULL;
        }
        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function collect_uploaded_documents($requestId, array $schema = array(), array $existingCounts = array())
    {
        $fields = isset($schema['fields']) && is_array($schema['fields']) ? $schema['fields'] : array();
        $fileFields = array();
        foreach ($fields as $field) {
            if (is_array($field) && ($field['type'] ?? '') === 'file' && !empty($field['key'])) {
                $fileFields[(string) $field['key']] = $field;
            }
        }

        $entries = array();
        $appendEntries = function ($fieldKey, $names, $types, $tmpNames, $errors, $sizes, $path = '') use (&$appendEntries, &$entries) {
            if (is_array($names)) {
                foreach ($names as $index => $name) {
                    $childPath = $path === '' ? (string) $index : $path . '.' . $index;
                    $appendEntries(
                        $fieldKey,
                        $name,
                        is_array($types) && array_key_exists($index, $types) ? $types[$index] : '',
                        is_array($tmpNames) && array_key_exists($index, $tmpNames) ? $tmpNames[$index] : '',
                        is_array($errors) && array_key_exists($index, $errors) ? $errors[$index] : UPLOAD_ERR_NO_FILE,
                        is_array($sizes) && array_key_exists($index, $sizes) ? $sizes[$index] : 0,
                        $childPath
                    );
                }
                return;
            }
            $entries[] = array(
                'field_key' => (string) $fieldKey,
                'name' => is_scalar($names) ? (string) $names : '',
                'type' => is_scalar($types) ? (string) $types : '',
                'tmp_name' => is_scalar($tmpNames) ? (string) $tmpNames : '',
                'error' => (int) $errors,
                'size' => (int) $sizes,
                'path' => $path
            );
        };

        if (isset($_FILES['supporting_files']) && is_array($_FILES['supporting_files'])) {
            $bucket = $_FILES['supporting_files'];
            $appendEntries('', $bucket['name'] ?? array(), $bucket['type'] ?? array(), $bucket['tmp_name'] ?? array(), $bucket['error'] ?? array(), $bucket['size'] ?? array());
        }
        if (isset($_FILES['warga_files']) && is_array($_FILES['warga_files'])) {
            $bucket = $_FILES['warga_files'];
            $names = isset($bucket['name']) && is_array($bucket['name']) ? $bucket['name'] : array();
            foreach ($names as $fieldKey => $fieldNames) {
                $appendEntries(
                    $fieldKey,
                    $fieldNames,
                    isset($bucket['type'][$fieldKey]) ? $bucket['type'][$fieldKey] : array(),
                    isset($bucket['tmp_name'][$fieldKey]) ? $bucket['tmp_name'][$fieldKey] : array(),
                    isset($bucket['error'][$fieldKey]) ? $bucket['error'][$fieldKey] : array(),
                    isset($bucket['size'][$fieldKey]) ? $bucket['size'][$fieldKey] : array()
                );
            }
        }

        $uploadedEntries = array_values(array_filter($entries, function ($entry) {
            return (int) $entry['error'] !== UPLOAD_ERR_NO_FILE;
        }));
        $counts = array();
        $genericCount = 0;
        foreach ($uploadedEntries as $entry) {
            $fieldKey = trim((string) $entry['field_key']);
            if ($fieldKey === '') {
                $genericCount++;
                continue;
            }
            if (!isset($fileFields[$fieldKey])) {
                return array('files' => array(), 'error' => 'Berkas formulir tidak dikenali. Muat ulang halaman lalu coba lagi.');
            }
            if (!isset($counts[$fieldKey])) $counts[$fieldKey] = 0;
            $counts[$fieldKey]++;
            if (empty($fileFields[$fieldKey]['multiple']) && $counts[$fieldKey] > 1) {
                return array('files' => array(), 'error' => 'Isian berkas "' . $fileFields[$fieldKey]['label'] . '" hanya menerima satu berkas.');
            }
        }
        if ($genericCount > 5) return array('files' => array(), 'error' => 'Maksimal lima berkas pendukung umum dapat dikirim.');
        if (count($uploadedEntries) > 10) return array('files' => array(), 'error' => 'Maksimal sepuluh berkas dapat dikirim dalam satu permohonan.');
        $replaceFieldKeys = array();
        foreach ($fileFields as $fieldKey => $field) {
            $existingCount = isset($existingCounts[$fieldKey]) ? (int) $existingCounts[$fieldKey] : 0;
            if (!empty($field['required']) && empty($counts[$fieldKey]) && $existingCount < 1) {
                return array('files' => array(), 'error' => 'Berkas "' . $field['label'] . '" wajib diunggah.');
            }
            if ($existingCount > 0 && !empty($counts[$fieldKey]) && empty($field['multiple'])) $replaceFieldKeys[$fieldKey] = TRUE;
        }
        if (!$uploadedEntries) return array('files' => array(), 'error' => NULL, 'paths' => array(), 'replace_field_keys' => array());

        $storage = $this->private_storage_path();
        if ($storage === NULL) return array('files' => array(), 'error' => 'Penyimpanan berkas belum siap.');
        $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf');
        $files = array();
        $paths = array();
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : NULL;
        $fail = function ($message) use (&$finfo, &$paths, &$replaceFieldKeys) {
            if ($finfo) {
                finfo_close($finfo);
                $finfo = NULL;
            }
            $this->cleanup_paths($paths);
            return array('files' => array(), 'error' => $message, 'paths' => array(), 'replace_field_keys' => array_keys($replaceFieldKeys));
        };
        if (!$finfo) return $fail('Pemeriksaan jenis berkas belum tersedia pada server.');

        foreach ($uploadedEntries as $entry) {
            $fieldKey = trim((string) $entry['field_key']);
            $field = $fieldKey !== '' && isset($fileFields[$fieldKey]) ? $fileFields[$fieldKey] : array();
            $maxMb = $field ? max(1, min(10, (int) ($field['max_size_mb'] ?? 5))) : 5;
            $size = (int) $entry['size'];
            $tmp = (string) $entry['tmp_name'];
            if ((int) $entry['error'] !== UPLOAD_ERR_OK) return $fail('Salah satu berkas gagal diunggah.');
            if ($size < 1 || $size > $maxMb * 1024 * 1024 || !is_uploaded_file($tmp)) {
                return $fail($field ? 'Berkas "' . $field['label'] . '" melebihi batas ' . $maxMb . ' MB.' : 'Setiap berkas harus berukuran maksimal 5 MB.');
            }
            $mime = finfo_file($finfo, $tmp);
            $mime = strtolower(trim((string) $mime));
            if (!isset($allowed[$mime])) return $fail('Jenis berkas hanya boleh JPG, PNG, atau PDF.');
            if ($field && !$this->file_accepts_mime($mime, (string) ($field['accept'] ?? ''))) {
                return $fail('Jenis berkas "' . $field['label'] . '" tidak sesuai dengan ketentuan layanan.');
            }
            $name = $requestId . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            $destination = $storage . DIRECTORY_SEPARATOR . 'requests' . DIRECTORY_SEPARATOR . $name;
            $directory = dirname($destination);
            if (!is_dir($directory) && !@mkdir($directory, 0750, TRUE) && !is_dir($directory)) return $fail('Folder berkas belum dapat dibuat.');
            if (!move_uploaded_file($tmp, $destination)) return $fail('Berkas belum dapat disimpan.');
            @chmod($destination, 0640);
            $paths[] = $destination;
            $files[] = array(
                'field_key' => $fieldKey !== '' ? $fieldKey : NULL,
                'original_name' => substr((string) $entry['name'], 0, 180),
                'stored_name' => $name,
                'storage_path' => $destination,
                'mime_type' => $mime,
                'file_size' => $size
            );
        }
        if ($finfo) finfo_close($finfo);
        return array('files' => $files, 'error' => NULL, 'paths' => $paths, 'replace_field_keys' => array_keys($replaceFieldKeys));
    }

    private function file_accepts_mime($mime, $accept)
    {
        $mime = strtolower(trim((string) $mime));
        $accept = trim((string) $accept);
        if ($accept === '') return TRUE;
        $parts = preg_split('/\s*,\s*/', strtolower($accept));
        if (!$parts) return TRUE;
        $extensionMimes = array('.jpg' => 'image/jpeg', '.jpeg' => 'image/jpeg', '.png' => 'image/png', '.pdf' => 'application/pdf');
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            if ($part === $mime) return TRUE;
            if (substr($part, -2) === '/*' && strpos($mime, substr($part, 0, -1)) === 0) return TRUE;
            if (isset($extensionMimes[$part]) && $extensionMimes[$part] === $mime) return TRUE;
        }
        return FALSE;
    }

    private function cleanup_paths(array $paths)
    {
        foreach ($paths as $path) if (is_string($path) && is_file($path)) @unlink($path);
    }

    private function demo_services()
    {
        return array(
            array('id' => 1, 'catalog_id' => 0, 'slug' => 'domisili', 'name' => 'Surat Keterangan Domisili', 'short_name' => 'Domisili', 'icon' => 'fa-home', 'description' => 'Keterangan tempat tinggal warga.', 'requirements' => array('Kartu Keluarga', 'Kartu Tanda Penduduk'), 'form_schema' => array('version' => 1, 'fields' => array()), 'schema_version' => 1, 'template_key' => 'domisili', 'is_catalog' => false),
            array('id' => 2, 'catalog_id' => 0, 'slug' => 'tidak-mampu', 'name' => 'Surat Keterangan Tidak Mampu', 'short_name' => 'Tidak Mampu', 'icon' => 'fa-hands-helping', 'description' => 'Keterangan kondisi sosial ekonomi warga.', 'requirements' => array('Kartu Keluarga', 'Kartu Tanda Penduduk'), 'form_schema' => array('version' => 1, 'fields' => array()), 'schema_version' => 1, 'template_key' => 'tidak-mampu', 'is_catalog' => false),
            array('id' => 3, 'catalog_id' => 0, 'slug' => 'usaha', 'name' => 'Surat Keterangan Usaha', 'short_name' => 'Keterangan Usaha', 'icon' => 'fa-store', 'description' => 'Keterangan kegiatan usaha warga.', 'requirements' => array('Kartu Keluarga', 'Kartu Tanda Penduduk', 'Keterangan lokasi usaha'), 'form_schema' => array('version' => 1, 'fields' => array()), 'schema_version' => 1, 'template_key' => 'usaha', 'is_catalog' => false)
        );
    }

    private function demo_defaults()
    {
        return array(
            array('id' => 'demo-request-000000000000000000000000000003', 'request_code' => 'SDW-2026-0003', 'citizen_user_id' => 99, 'service_slug' => 'usaha', 'service_name' => 'Surat Keterangan Usaha', 'service_icon' => 'fa-store', 'status' => 'submitted', 'submitted_at' => '2026-08-31 16:20:00', 'updated_at' => '2026-08-31 16:20:00', 'purpose' => 'Persyaratan pengajuan bantuan usaha', 'note' => 'Usaha kios sembako berada di Kampung Araboda.', 'local_reference' => NULL, 'document_path' => NULL, 'citizen_name' => 'Mabel Wenda', 'citizen_phone' => '081234567893', 'village_name' => 'Kampung Araboda'),
            array('id' => 'demo-request-000000000000000000000000000001', 'request_code' => 'SDW-2026-0001', 'citizen_user_id' => 1, 'service_slug' => 'domisili', 'service_name' => 'Surat Keterangan Domisili', 'service_icon' => 'fa-home', 'status' => 'issued', 'submitted_at' => '2026-08-26 09:14:00', 'updated_at' => '2026-08-27 15:40:00', 'purpose' => 'Keperluan administrasi sekolah', 'note' => '', 'local_reference' => 'SKD/01/DEMO/VIII/2026', 'document_path' => NULL, 'citizen_name' => 'Yotam Wamena', 'citizen_phone' => '081234567890', 'village_name' => 'Kampung Araboda'),
            array('id' => 'demo-request-000000000000000000000000000002', 'request_code' => 'SDW-2026-0002', 'citizen_user_id' => 1, 'service_slug' => 'tidak-mampu', 'service_name' => 'Surat Keterangan Tidak Mampu', 'service_icon' => 'fa-hands-helping', 'status' => 'verified', 'submitted_at' => '2026-08-30 10:22:00', 'updated_at' => '2026-08-30 13:05:00', 'purpose' => 'Pengajuan bantuan pendidikan', 'note' => 'Mohon diproses sesuai jadwal pelayanan.', 'local_reference' => NULL, 'document_path' => NULL, 'citizen_name' => 'Yotam Wamena', 'citizen_phone' => '081234567890', 'village_name' => 'Kampung Araboda')
        );
    }

    private function demo_requests()
    {
        $saved = $this->session->userdata('warga_demo_requests');
        if (!is_array($saved)) $saved = array();
        $rows = array_merge($saved, $this->demo_defaults());
        $overrides = $this->session->userdata('warga_demo_request_overrides');
        if (!is_array($overrides)) $overrides = array();
        foreach ($rows as &$row) {
            if (isset($overrides[$row['id']]) && is_array($overrides[$row['id']])) $row = array_merge($row, $overrides[$row['id']]);
        }
        unset($row);
        usort($rows, function ($left, $right) { return strcmp((string) $right['submitted_at'], (string) $left['submitted_at']); });
        return $rows;
    }

    private function find_demo_request($id)
    {
        foreach ($this->demo_requests() as $row) if ((string) $row['id'] === (string) $id) return $row;
        return NULL;
    }

    private function apply_staff_scope(array $user)
    {
        $role = isset($user['role_slug']) ? $user['role_slug'] : '';
        if ($role === 'admin-pusat') return;
        if ($role === 'admin-kabupaten' && !empty($user['regency_code'])) {
            $this->db->where('v.regency_code', $user['regency_code']);
            return;
        }
        if (!empty($user['village_id'])) $this->db->where('sr.village_id', $user['village_id']);
        else $this->db->where('sr.id', '');
    }

    private function demo_staff_request_rows()
    {
        $rows = $this->demo_requests();
        foreach ($rows as &$row) {
            if (empty($row['citizen_name'])) $row['citizen_name'] = 'Yotam Wamena';
            if (empty($row['citizen_phone'])) $row['citizen_phone'] = '081234567890';
            if (empty($row['village_name'])) $row['village_name'] = 'Kampung Araboda';
        }
        unset($row);
        return $rows;
    }

    public function service_types($villageId = '')
    {
        if (warga_demo_mode()) return $this->demo_services();
        if (!warga_database_available()) return array();
        $this->ensure_catalog_schema();
        $villageId = trim((string) $villageId);
        if ($villageId !== '') {
            $rows = $this->db->select('c.*, st.id AS legacy_service_type_id')
                ->from('village_service_catalog c')
                ->join('service_types st', 'st.slug=c.service_key', 'left')
                ->where(array('c.village_id' => $villageId, 'c.is_active' => 1))
                ->order_by('c.sort_order', 'ASC')->order_by('c.name', 'ASC')->get()->result_array();
            foreach ($rows as &$row) $this->ensure_legacy_service_type($row);
            unset($row);
            return array_map(array($this, 'normalise_catalog_row'), $rows);
        }

        $rows = $this->db->where('is_active', 1)->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get('service_types')->result_array();
        foreach ($rows as &$row) {
            $row['requirements'] = json_decode((string) $row['requirements_json'], TRUE);
            if (!is_array($row['requirements'])) $row['requirements'] = array();
            $row['form_schema'] = array('version' => 1, 'fields' => array());
            $row['schema_version'] = 1;
            $row['catalog_id'] = 0;
            $row['is_catalog'] = false;
            $row['submission_enabled'] = true;
            $row['availability_note'] = '';
            $row['icon'] = $row['icon'] ?: 'fa-file-alt';
        }
        unset($row);
        return $rows;
    }

    private function normalise_catalog_row(array $row)
    {
        $requirements = json_decode((string) (isset($row['requirements_json']) ? $row['requirements_json'] : ''), TRUE);
        $schema = json_decode((string) (isset($row['form_schema_json']) ? $row['form_schema_json'] : ''), TRUE);
        if (!is_array($requirements)) $requirements = array();
        if (!is_array($schema) || !isset($schema['fields']) || !is_array($schema['fields'])) $schema = array('version' => 1, 'fields' => array());
        $legacyId = isset($row['legacy_service_type_id']) ? (int) $row['legacy_service_type_id'] : 0;
        $catalogId = isset($row['id']) ? (int) $row['id'] : 0;
        return array(
            // service_requests.service_type_id references service_types.id;
            // a per-village catalog ID must never be used in that column.
            'id' => $legacyId,
            'legacy_service_type_id' => $legacyId,
            'catalog_id' => $catalogId,
            'slug' => (string) (isset($row['service_key']) ? $row['service_key'] : ''),
            'name' => (string) (isset($row['name']) ? $row['name'] : ''),
            'short_name' => (string) (isset($row['short_name']) ? $row['short_name'] : ''),
            'icon' => !empty($row['icon']) ? (string) $row['icon'] : 'fa-file-alt',
            'description' => (string) (isset($row['description']) ? $row['description'] : ''),
            'requirements' => $requirements,
            'form_schema' => $schema,
            'schema_version' => (int) $schema['version'],
            'template_key' => (string) (isset($row['template_key']) ? $row['template_key'] : ''),
            'submission_enabled' => !isset($row['submission_enabled']) || (int) $row['submission_enabled'] === 1,
            'availability_note' => (string) (isset($row['availability_note']) ? $row['availability_note'] : ''),
            'is_catalog' => true
        );
    }

    /**
     * Resolve the legacy service_types row required by the existing foreign
     * key. Catalog rows are per-village and their IDs are not interchangeable.
     */
    private function ensure_legacy_service_type(array &$row)
    {
        $legacyId = isset($row['legacy_service_type_id']) ? (int) $row['legacy_service_type_id'] : 0;
        if ($legacyId > 0) return $legacyId;
        $slug = strtolower(trim((string) (isset($row['service_key']) ? $row['service_key'] : '')));
        if ($slug === '') return 0;
        $existing = $this->db->where('slug', $slug)->limit(1)->get('service_types')->row_array();
        if ($existing && !empty($existing['id'])) {
            $row['legacy_service_type_id'] = (int) $existing['id'];
            return (int) $existing['id'];
        }

        $limit = function ($value, $length) {
            $value = trim((string) $value);
            return function_exists('mb_substr') ? mb_substr($value, 0, (int) $length, 'UTF-8') : substr($value, 0, (int) $length);
        };
        $legacy = array(
            'slug' => $slug,
            'name' => $limit(isset($row['name']) ? $row['name'] : $slug, 180),
            'short_name' => $limit(isset($row['short_name']) ? $row['short_name'] : $slug, 100),
            'icon' => !empty($row['icon']) ? $limit($row['icon'], 80) : 'fa-file-alt',
            'description' => !empty($row['description']) ? $limit($row['description'], 500) : NULL,
            'requirements_json' => !empty($row['requirements_json']) ? (string) $row['requirements_json'] : json_encode(array()),
            'template_key' => !empty($row['template_key']) ? $limit($row['template_key'], 100) : $slug,
            'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
            'is_active' => 1
        );
        if (!$this->db->insert('service_types', $legacy)) {
            // A concurrent request may have created the same unique slug.
            $existing = $this->db->where('slug', $slug)->limit(1)->get('service_types')->row_array();
            if (!$existing || empty($existing['id'])) return 0;
            $row['legacy_service_type_id'] = (int) $existing['id'];
            return (int) $existing['id'];
        }
        $legacyId = (int) $this->db->insert_id();
        $row['legacy_service_type_id'] = $legacyId;
        return $legacyId;
    }

    private function service_for_user($slug, $villageId)
    {
        $slug = strtolower(trim((string) $slug));
        $villageId = trim((string) $villageId);
        $this->ensure_catalog_schema();
        if ($villageId !== '') {
            $row = $this->db->select('c.*, st.id AS legacy_service_type_id')
                ->from('village_service_catalog c')->join('service_types st', 'st.slug=c.service_key', 'left')
                ->where(array('c.village_id' => $villageId, 'c.service_key' => $slug, 'c.is_active' => 1))
                ->limit(1)->get()->row_array();
            if ($row) {
                $legacyId = $this->ensure_legacy_service_type($row);
                if ($legacyId < 1) return NULL;
                return $this->normalise_catalog_row($row);
            }
            return NULL;
        }
        $row = $this->db->where(array('slug' => $slug, 'is_active' => 1))->limit(1)->get('service_types')->row_array();
        if (!$row) return NULL;
        $row['requirements'] = json_decode((string) $row['requirements_json'], TRUE);
        if (!is_array($row['requirements'])) $row['requirements'] = array();
        $row['form_schema'] = array('version' => 1, 'fields' => array());
        $row['schema_version'] = 1;
        $row['catalog_id'] = 0;
        $row['is_catalog'] = false;
        $row['submission_enabled'] = true;
        $row['availability_note'] = '';
        return $row;
    }

    private function validate_dynamic_fields(array $service, array $submitted)
    {
        $schema = isset($service['form_schema']) && is_array($service['form_schema']) ? $service['form_schema'] : array();
        $fields = isset($schema['fields']) && is_array($schema['fields']) ? $schema['fields'] : array();
        $submitted = is_array($submitted) ? $submitted : array();
        $known = array();
        foreach ($fields as $field) if (is_array($field) && !empty($field['key'])) $known[(string) $field['key']] = $field;
        foreach ($submitted as $key => $value) {
            if (!isset($known[$key]) && (is_array($value) || trim((string) $value) !== '')) return array('success' => false, 'message' => 'Isian formulir tidak dikenali. Muat ulang formulir lalu coba lagi.');
        }
        $values = array();
        foreach ($known as $key => $field) {
            if (($field['type'] ?? '') === 'file') continue;
            $value = isset($submitted[$key]) && is_scalar($submitted[$key]) ? trim((string) $submitted[$key]) : '';
            $type = (string) ($field['type'] ?? 'text');
            $max = max(1, min(5000, (int) ($field['max_length'] ?? 500)));
            if (function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') > $max : strlen($value) > $max) return array('success' => false, 'message' => 'Isian "' . $field['label'] . '" terlalu panjang.');
            if (!empty($field['required']) && $value === '') return array('success' => false, 'message' => 'Isian "' . $field['label'] . '" wajib diisi.');
            if ($value !== '' && $type === 'select') {
                $allowed = array();
                foreach (isset($field['options']) && is_array($field['options']) ? $field['options'] : array() as $option) if (is_array($option)) $allowed[] = (string) ($option['value'] ?? '');
                if (!in_array($value, $allowed, true)) return array('success' => false, 'message' => 'Pilihan pada isian "' . $field['label'] . '" tidak valid.');
            }
            if ($value !== '' && $type === 'date') {
                $date = DateTime::createFromFormat('Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value) return array('success' => false, 'message' => 'Tanggal pada isian "' . $field['label'] . '" tidak valid.');
            }
            if ($value !== '' && $type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) return array('success' => false, 'message' => 'Email pada isian "' . $field['label'] . '" tidak valid.');
            if ($value !== '' && $type === 'number' && !is_numeric($value)) return array('success' => false, 'message' => 'Angka pada isian "' . $field['label'] . '" tidak valid.');
            if ($value !== '' && $type === 'tel' && !preg_match('/^[0-9+() .-]{3,40}$/', $value)) return array('success' => false, 'message' => 'Nomor telepon pada isian "' . $field['label'] . '" tidak valid.');
            $values[$key] = $value;
        }
        return array('success' => true, 'values' => $values, 'schema_version' => (int) ($schema['version'] ?? 1));
    }

    private function validate_request_text($purpose, $note)
    {
        $purpose = trim((string) $purpose);
        $note = trim((string) $note);
        $purposeLength = function_exists('mb_strlen') ? mb_strlen($purpose, 'UTF-8') : strlen($purpose);
        $noteLength = function_exists('mb_strlen') ? mb_strlen($note, 'UTF-8') : strlen($note);
        if ($purposeLength < 5 || $purposeLength > 500) {
            return array('success' => FALSE, 'message' => 'Keperluan harus 5 sampai 500 karakter.');
        }
        if ($noteLength > 1000) {
            return array('success' => FALSE, 'message' => 'Catatan maksimal 1.000 karakter.');
        }
        return array('success' => TRUE);
    }

    public function for_user($userId)
    {
        if (warga_demo_mode()) return array_values(array_filter($this->demo_requests(), function ($row) use ($userId) { return isset($row['citizen_user_id']) && (int) $row['citizen_user_id'] === (int) $userId; }));
        if (!warga_database_available()) return array();
        $this->ensure_catalog_schema();
        $rows = $this->db->select('sr.*, COALESCE(vc.service_key, st.slug) AS service_slug, COALESCE(vc.name, st.name) AS service_name, COALESCE(vc.icon, st.icon) AS service_icon, vc.form_schema_json AS catalog_form_schema_json, vc.template_key AS catalog_template_key, v.name AS village_name', FALSE)
            ->from('service_requests sr')->join('service_types st', 'st.id=sr.service_type_id')->join('village_service_catalog vc', 'vc.id=sr.catalog_service_id AND vc.village_id=sr.village_id', 'left', FALSE)->join('village_tenants v', 'v.id=sr.village_id', 'left')
            ->where('sr.citizen_user_id', (int) $userId)->order_by('sr.submitted_at', 'DESC')->get()->result_array();
        foreach ($rows as &$row) $row = $this->normalize_row($row);
        unset($row);
        return $rows;
    }

    public function paginated_for_user($userId, array $filters = array(), $dateField = 'submitted_at')
    {
        $dateField = $dateField === 'updated_at' ? 'updated_at' : 'submitted_at';
        $filters = $this->normalize_list_filters($filters);
        $requestedPage = $filters['page'];
        unset($filters['page']);
        $perPage = 10;
        $total = 0;
        $rows = array();

        if ((int) $userId > 0 && warga_demo_mode()) {
            $rows = array_values(array_filter($this->demo_requests(), function ($row) use ($userId, $filters, $dateField) {
                if (!isset($row['citizen_user_id']) || (int) $row['citizen_user_id'] !== (int) $userId) return FALSE;
                if ($filters['q'] !== '') {
                    $name = isset($row['service_name']) ? (string) $row['service_name'] : '';
                    $match = function_exists('mb_stripos')
                        ? mb_stripos($name, $filters['q'], 0, 'UTF-8')
                        : stripos($name, $filters['q']);
                    if ($match === FALSE) return FALSE;
                }
                if ($filters['date'] !== '' && substr((string) ($row[$dateField] ?? ''), 0, 10) !== $filters['date']) return FALSE;
                if ($filters['status'] === 'issued' && $row['status'] !== 'issued') return FALSE;
                if ($filters['status'] === 'active' && !in_array($row['status'], array('submitted', 'verified', 'approved', 'syncing', 'revision'), TRUE)) return FALSE;
                return TRUE;
            }));
            usort($rows, function ($left, $right) use ($dateField) {
                $dateOrder = strcmp((string) ($right[$dateField] ?? ''), (string) ($left[$dateField] ?? ''));
                return $dateOrder !== 0 ? $dateOrder : strcmp((string) $right['id'], (string) $left['id']);
            });
            $total = count($rows);
        } elseif ((int) $userId > 0 && warga_database_available()) {
            $this->ensure_catalog_schema();
            $this->apply_user_list_query($userId, $filters, $dateField);
            $total = (int) $this->db->count_all_results();
        }

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($requestedPage, $pages);
        $offset = ($page - 1) * $perPage;
        if (warga_demo_mode()) {
            $rows = array_slice($rows, $offset, $perPage);
        } elseif ($total > 0) {
            $this->apply_user_list_query($userId, $filters, $dateField);
            $rows = $this->db->select('sr.*, COALESCE(vc.service_key, st.slug) AS service_slug, COALESCE(vc.name, st.name) AS service_name, COALESCE(vc.icon, st.icon) AS service_icon, vc.form_schema_json AS catalog_form_schema_json, vc.template_key AS catalog_template_key, v.name AS village_name', FALSE)
                ->order_by('sr.' . $dateField, 'DESC')->order_by('sr.id', 'DESC')
                ->limit($perPage, $offset)->get()->result_array();
            foreach ($rows as &$row) $row = $this->normalize_row($row);
            unset($row);
        }

        return array(
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + count($rows), $total),
            'filters' => $filters
        );
    }

    private function normalize_list_filters(array $filters)
    {
        $query = isset($filters['q']) && is_scalar($filters['q']) ? trim((string) $filters['q']) : '';
        $query = function_exists('mb_substr') ? mb_substr($query, 0, 180, 'UTF-8') : substr($query, 0, 180);
        $date = isset($filters['date']) && is_scalar($filters['date']) ? trim((string) $filters['date']) : '';
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            $date = '';
        }
        $status = isset($filters['status']) && is_scalar($filters['status']) ? (string) $filters['status'] : 'all';
        if (!in_array($status, array('all', 'active', 'issued'), TRUE)) $status = 'all';
        $page = isset($filters['page']) && is_scalar($filters['page']) ? (string) $filters['page'] : '1';
        $page = ctype_digit($page) ? max(1, (int) $page) : 1;
        return array('q' => $query, 'date' => $date, 'status' => $status, 'page' => $page);
    }

    private function apply_user_list_query($userId, array $filters, $dateField)
    {
        $this->db->from('service_requests sr')
            ->join('service_types st', 'st.id=sr.service_type_id')
            ->join('village_service_catalog vc', 'vc.id=sr.catalog_service_id AND vc.village_id=sr.village_id', 'left', FALSE)
            ->join('village_tenants v', 'v.id=sr.village_id', 'left')
            ->where('sr.citizen_user_id', (int) $userId);
        if ($filters['q'] !== '') $this->db->like('COALESCE(vc.name, st.name)', $filters['q'], 'both');
        if ($filters['date'] !== '') {
            $this->db->where('sr.' . $dateField . ' >=', $filters['date'] . ' 00:00:00')
                ->where('sr.' . $dateField . ' <=', $filters['date'] . ' 23:59:59');
        }
        if ($filters['status'] === 'issued') $this->db->where('sr.status', 'issued');
        if ($filters['status'] === 'active') $this->db->where_in('sr.status', array('submitted', 'verified', 'approved', 'syncing', 'revision'));
    }

    public function summary($userId)
    {
        if (warga_demo_mode()) {
            $rows = $this->for_user($userId);
            $summary = array('total' => count($rows), 'active' => 0, 'issued' => 0, 'revision' => 0);
            foreach ($rows as $row) {
                if (in_array($row['status'], array('submitted', 'verified', 'approved', 'syncing'), TRUE)) $summary['active']++;
                if ($row['status'] === 'issued') $summary['issued']++;
                if ($row['status'] === 'revision') $summary['revision']++;
            }
            return $summary;
        }
        if (!warga_database_available()) return array('total' => 0, 'active' => 0, 'issued' => 0, 'revision' => 0);
        // The dashboard only needs counters. Avoid loading and normalizing
        // every historical request just to calculate four numbers.
        $row = $this->db->select("COUNT(*) AS total,
                SUM(CASE WHEN status IN ('submitted','verified','approved','syncing') THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) AS issued,
                SUM(CASE WHEN status = 'revision' THEN 1 ELSE 0 END) AS revision", FALSE)
            ->where('citizen_user_id', (int) $userId)->limit(1)->get('service_requests')->row_array();
        return array('total' => (int) ($row['total'] ?? 0), 'active' => (int) ($row['active'] ?? 0),
            'issued' => (int) ($row['issued'] ?? 0), 'revision' => (int) ($row['revision'] ?? 0));
    }

    public function find_for_user($id, $userId)
    {
        if (warga_demo_mode()) {
            foreach ($this->demo_requests() as $row) if ((string) $row['id'] === (string) $id && isset($row['citizen_user_id']) && (int) $row['citizen_user_id'] === (int) $userId) return $row;
            return NULL;
        }
        if (!warga_database_available()) return NULL;
        $this->ensure_catalog_schema();
        $row = $this->db->select('sr.*, COALESCE(vc.service_key, st.slug) AS service_slug, COALESCE(vc.name, st.name) AS service_name, COALESCE(vc.icon, st.icon) AS service_icon, vc.form_schema_json AS catalog_form_schema_json, vc.template_key AS catalog_template_key, v.name AS village_name', FALSE)
            ->from('service_requests sr')->join('service_types st', 'st.id=sr.service_type_id')->join('village_service_catalog vc', 'vc.id=sr.catalog_service_id AND vc.village_id=sr.village_id', 'left', FALSE)->join('village_tenants v', 'v.id=sr.village_id', 'left')
            ->where(array('sr.id' => (string) $id, 'sr.citizen_user_id' => (int) $userId))->get()->row_array();
        return $row ? $this->normalize_row($row) : NULL;
    }

    public function for_staff(array $user, $status = NULL)
    {
        if (warga_demo_mode()) {
            $rows = $this->demo_staff_request_rows();
            if ($status !== NULL && $status !== '') $rows = array_values(array_filter($rows, function ($row) use ($status) { return $row['status'] === $status; }));
            return $rows;
        }
        if (!warga_database_available()) return array();
        $this->ensure_catalog_schema();
        $this->db->select('sr.*, COALESCE(vc.service_key, st.slug) AS service_slug, COALESCE(vc.name, st.name) AS service_name, COALESCE(vc.icon, st.icon) AS service_icon, vc.form_schema_json AS catalog_form_schema_json, vc.template_key AS catalog_template_key, u.name AS citizen_name, u.phone AS citizen_phone, u.email AS citizen_email, v.name AS village_name, v.regency_code, v.regency_name', FALSE);
        $this->db->from('service_requests sr');
        $this->db->join('service_types st', 'st.id=sr.service_type_id');
        $this->db->join('village_service_catalog vc', 'vc.id=sr.catalog_service_id AND vc.village_id=sr.village_id', 'left', FALSE);
        $this->db->join('users u', 'u.id=sr.citizen_user_id');
        $this->db->join('village_tenants v', 'v.id=sr.village_id');
        $this->apply_staff_scope($user);
        if ($status !== NULL && $status !== '') $this->db->where('sr.status', $status);
        $rows = $this->db->order_by('sr.submitted_at', 'DESC')->get()->result_array();
        foreach ($rows as &$row) $row = $this->normalize_row($row);
        unset($row);
        return $rows;
    }

    public function staff_summary(array $user)
    {
        $rows = $this->for_staff($user);
        $summary = array('total' => count($rows), 'verification' => 0, 'approval' => 0, 'issued' => 0, 'revision' => 0, 'rejected' => 0);
        foreach ($rows as $row) {
            if ($row['status'] === 'submitted') $summary['verification']++;
            if ($row['status'] === 'verified') $summary['approval']++;
            if ($row['status'] === 'issued') $summary['issued']++;
            if ($row['status'] === 'revision') $summary['revision']++;
            if ($row['status'] === 'rejected') $summary['rejected']++;
        }
        return $summary;
    }

    public function find_for_staff($id, array $user)
    {
        if (warga_demo_mode()) return $this->find_demo_request($id);
        if (!warga_database_available()) return NULL;
        $this->ensure_catalog_schema();
        $this->db->select('sr.*, COALESCE(vc.service_key, st.slug) AS service_slug, COALESCE(vc.name, st.name) AS service_name, COALESCE(vc.icon, st.icon) AS service_icon, vc.form_schema_json AS catalog_form_schema_json, vc.template_key AS catalog_template_key, u.name AS citizen_name, u.phone AS citizen_phone, u.email AS citizen_email, v.name AS village_name, v.regency_code, v.regency_name', FALSE);
        $this->db->from('service_requests sr');
        $this->db->join('service_types st', 'st.id=sr.service_type_id');
        $this->db->join('village_service_catalog vc', 'vc.id=sr.catalog_service_id AND vc.village_id=sr.village_id', 'left', FALSE);
        $this->db->join('users u', 'u.id=sr.citizen_user_id');
        $this->db->join('village_tenants v', 'v.id=sr.village_id');
        $this->db->where('sr.id', (string) $id);
        $this->apply_staff_scope($user);
        $row = $this->db->get()->row_array();
        return $row ? $this->normalize_row($row) : NULL;
    }

    public function allowed_actions(array $user, array $request)
    {
        $role = isset($user['role_slug']) ? $user['role_slug'] : '';
        $status = isset($request['status']) ? $request['status'] : '';
        $this->load->model('Community_model');
        // The workflow is mutable village configuration. Never authorize a
        // staff action from the request payload, which may contain an older
        // snapshot captured when the citizen submitted the request.
        $settings = $this->Community_model->workflow($request['village_id'] ?? ($user['village_id'] ?? ''));
        require_once dirname(__DIR__) . '/libraries/Verification_workflow.php';
        return Verification_workflow::actions($role, $status, $settings);
    }

    public function apply_action($id, array $user, $action, $note = '')
    {
        $request = $this->find_for_staff($id, $user);
        if (!$request) return array('success' => FALSE, 'message' => 'Permohonan tidak ditemukan atau bukan wilayah kerja Anda.');
        $institution = $this->institution_for_context($request + $user);
        $institutionLower = function_exists('mb_strtolower') ? mb_strtolower($institution, 'UTF-8') : strtolower($institution);
        $actions = array(
            'verify' => array('status' => 'verified', 'label' => 'Diverifikasi Sekretaris ' . $institution, 'default_note' => 'Berkas dan data awal telah diperiksa.'),
            'approve' => array('status' => 'approved', 'label' => 'Disetujui Kepala ' . $institution, 'default_note' => 'Permohonan disetujui untuk diproses.'),
            'revision' => array('status' => 'revision', 'label' => 'Perlu perbaikan', 'default_note' => 'Permohonan memerlukan perbaikan data atau berkas.'),
            'reject' => array('status' => 'rejected', 'label' => 'Permohonan ditolak', 'default_note' => 'Permohonan belum dapat disetujui ' . $institutionLower . '.')
        );
        if (!isset($actions[$action]) || !in_array($action, $this->allowed_actions($user, $request), TRUE)) return array('success' => FALSE, 'message' => 'Tindakan tidak tersedia untuk peran dan status ini.');
        $next = $actions[$action];
        if ($action === 'approve' && $user['role_slug'] === 'sekdes') $next['label'] = 'Disetujui Sekretaris ' . $institution;
        if (mb_strlen((string)$note) > 1000) return array('success'=>false,'message'=>'Catatan maksimal 1000 karakter.');
        if (in_array($action, array('revision', 'reject'), TRUE) && trim((string) $note) === '') return array('success' => FALSE, 'message' => 'Alasan wajib diisi untuk perbaikan atau penolakan.');
        $note = trim((string) $note) !== '' ? trim((string) $note) : $next['default_note'];
        $now = date('Y-m-d H:i:s');
        if (warga_demo_mode()) {
            $historyMap = $this->session->userdata('warga_demo_history');
            if (!is_array($historyMap)) $historyMap = array();
            $history = isset($historyMap[(string) $id]) && is_array($historyMap[(string) $id]) ? $historyMap[(string) $id] : $this->history($id);
            $overrides = $this->session->userdata('warga_demo_request_overrides');
            if (!is_array($overrides)) $overrides = array();
            $overrides[(string) $id] = array('status' => $next['status'], 'updated_at' => $now);
            if ($next['status'] === 'issued') $overrides[(string) $id]['local_reference'] = 'DEMO/' . date('YmdHis');
            $this->session->set_userdata('warga_demo_request_overrides', $overrides);
            $history[] = array('status' => $next['status'], 'label' => $next['label'], 'note' => $note, 'occurred_at' => $now, 'actor_name' => $user['name']);
            $historyMap[(string) $id] = $history;
            $this->session->set_userdata('warga_demo_history', $historyMap);
            return array('success' => TRUE, 'status' => $next['status']);
        }
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database layanan warga sedang tidak tersedia. Silakan coba lagi.');

        $currentEventVersion = max(1, (int) ($request['event_version'] ?? 1));
        $eventVersion = $currentEventVersion + 1;
        $payload = json_encode(array('request_id' => $request['id'], 'request_code' => $request['request_code'], 'status' => $next['status'], 'note' => $note, 'actor_name' => $user['name'], 'actor_role' => $user['role_slug'], 'event_version' => $eventVersion), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$this->db->trans_begin()) {
            return array('success' => FALSE, 'message' => 'Tindakan belum dapat dimulai. Silakan coba lagi.');
        }
        $this->db->where(array('id' => (string) $id, 'status' => $request['status']))
            ->where('event_version', $currentEventVersion)
            ->update('service_requests', array('status' => $next['status'], 'event_version' => $eventVersion));
        $updated = $this->db->affected_rows();
        if ($updated !== 1) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Status berubah karena diproses pengguna lain. Muat ulang halaman.');
        }
        $this->db->insert('request_status_history', array('request_id' => (string) $id, 'from_status' => $request['status'], 'to_status' => $next['status'], 'note' => $note, 'actor_id' => (int) $user['id']));
        $notificationId = warga_uuid();
        $this->db->insert('notifications', array('id' => $notificationId, 'user_id' => (int) $request['citizen_user_id'], 'request_id' => (string) $id, 'title' => $request['service_name'], 'message' => $next['label'] . '. ' . $note));
        $this->db->insert('warga_notification_targets', array('notification_id' => $notificationId, 'target_path' => 'permohonan/' . (string) $id));
        if ($next['status'] === 'verified') $this->Community_model->notify_staff($request['village_id'],
            'Permohonan menunggu persetujuan', 'Sekretaris ' . $institution . ' telah memverifikasi permohonan surat.',
            'petugas/permohonan/'.$id, array('kepala-desa'), $id);
        $this->db->insert('sync_messages', array('id' => warga_uuid(), 'village_id' => $request['village_id'], 'aggregate_type' => 'service_request', 'aggregate_id' => (string) $id, 'direction' => 'cloud_to_local', 'operation' => 'status_update', 'event_version' => $eventVersion, 'payload_json' => $payload, 'status' => 'pending', 'idempotency_key' => 'request-status:' . $id . ':' . $next['status'] . ':' . bin2hex(random_bytes(6))));
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            return array('success' => FALSE, 'message' => 'Tindakan belum dapat disimpan.');
        }
        if (!$this->db->trans_commit()) {
            return array('success' => FALSE, 'message' => 'Tindakan belum dapat disimpan.');
        }
        return array('success' => TRUE, 'status' => $next['status']);
    }

    public function documents_for_staff($requestId, array $user)
    {
        if (warga_demo_mode()) {
            $request = $this->find_demo_request($requestId);
            return !empty($request['documents']) ? $request['documents'] : array();
        }
        if (!warga_database_available()) return array();
        $request = $this->find_for_staff($requestId, $user);
        if (!$request) return array();
        return $this->db->select('d.id,d.field_key,d.original_name,d.mime_type,d.file_size')->from('request_documents d')->where('d.request_id', (string) $requestId)->order_by('d.created_at', 'ASC')->get()->result_array();
    }

    public function document_for_staff($documentId, array $user)
    {
        if (warga_demo_mode() || !warga_database_available()) return NULL;
        $this->db->select('d.*, r.id AS request_id')->from('request_documents d')->join('service_requests r', 'r.id=d.request_id')->where('d.id', (string) $documentId);
        $request = $this->db->get()->row_array();
        if (!$request || !$this->find_for_staff($request['request_id'], $user)) return NULL;
        return $request;
    }

    public function history($requestId)
    {
        if (warga_demo_mode()) {
            $historyMap = $this->session->userdata('warga_demo_history');
            if (is_array($historyMap) && isset($historyMap[(string) $requestId]) && is_array($historyMap[(string) $requestId])) return $historyMap[(string) $requestId];
            $request = $this->find_demo_request($requestId);
            if (!$request) return array();
            $history = array(array('status' => 'submitted', 'label' => 'Permohonan diajukan', 'note' => 'Data berhasil diterima oleh sistem.', 'occurred_at' => $request['submitted_at']));
            $steps = array(
                'verified' => array('label' => 'Diverifikasi Sekretaris ' . $this->institution_for_context($request), 'note' => 'Berkas dan data awal telah diperiksa.'),
                'approved' => array('label' => 'Disetujui Kepala ' . $this->institution_for_context($request), 'note' => 'Permohonan disetujui untuk diproses.'),
                'issued' => array('label' => 'Surat diterbitkan', 'note' => 'Dokumen resmi telah diterbitkan ' . $this->institution_lower($request) . '.'),
                'revision' => array('label' => 'Perlu perbaikan', 'note' => 'Permohonan memerlukan perbaikan data atau berkas.'),
                'rejected' => array('label' => 'Permohonan ditolak', 'note' => 'Permohonan belum dapat disetujui ' . $this->institution_lower($request) . '.')
            );
            $statusOrder = array('verified', 'approved', 'issued');
            foreach ($statusOrder as $status) {
                if ($request['status'] === $status || ($status === 'verified' && in_array($request['status'], array('approved', 'issued'), TRUE)) || ($status === 'approved' && $request['status'] === 'issued')) {
                    $history[] = array('status' => $status, 'label' => $steps[$status]['label'], 'note' => $steps[$status]['note'], 'occurred_at' => $request['updated_at']);
                }
            }
            if (in_array($request['status'], array('revision', 'rejected'), TRUE)) $history[] = array('status' => $request['status'], 'label' => $steps[$request['status']]['label'], 'note' => $steps[$request['status']]['note'], 'occurred_at' => $request['updated_at']);
            return $history;
        }
        if (!warga_database_available()) return array();
        $rows = $this->db->select('h.*, h.to_status AS status, u.name AS actor_name')->from('request_status_history h')->join('users u', 'u.id=h.actor_id', 'left')
            ->where('h.request_id', (string) $requestId)->order_by('h.occurred_at', 'ASC')->get()->result_array();
        foreach ($rows as &$row) $row['label'] = warga_status_text($row['status']);
        unset($row);
        return $rows;
    }

    public function create(array $user, array $data)
    {
        $serviceSlug = strtolower(trim((string) ($data['service_type'] ?? '')));
        $purpose = trim((string) ($data['purpose'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        $formFields = isset($data['form_fields']) && is_array($data['form_fields']) ? $data['form_fields'] : array();
        $textValidation = $this->validate_request_text($purpose, $note);
        if (empty($textValidation['success'])) return $textValidation;
        if (warga_demo_mode()) {
            $service = NULL;
            foreach ($this->demo_services() as $candidate) if ($candidate['slug'] === $serviceSlug) $service = $candidate;
            if (!$service) return array('success' => FALSE, 'message' => 'Jenis layanan tidak ditemukan.');
            $validated = $this->validate_dynamic_fields($service, $formFields);
            if (empty($validated['success'])) return array('success' => FALSE, 'message' => $validated['message']);
            $id = warga_uuid();
            $now = date('Y-m-d H:i:s');
            $created = array('id' => $id, 'request_code' => 'SDW-' . date('Y') . '-' . strtoupper(substr(str_replace('-', '', $id), 0, 6)), 'citizen_user_id' => (int) $user['id'], 'service_slug' => $service['slug'], 'service_name' => $service['name'], 'service_icon' => $service['icon'], 'status' => 'submitted', 'submitted_at' => $now, 'updated_at' => $now, 'purpose' => $purpose, 'note' => $note, 'form_data' => $validated['values'], 'form_schema' => $service['form_schema'], 'form_schema_version' => $validated['schema_version'], 'local_reference' => NULL, 'document_path' => NULL, 'citizen_name' => $user['name'], 'citizen_phone' => $user['phone'], 'village_name' => $user['village_name']);
            $uploaded = $this->collect_uploaded_documents($id, $service['form_schema']);
            if (!empty($uploaded['error'])) return array('success' => FALSE, 'message' => $uploaded['error']);
            $created['documents'] = array_map(function ($file) { return array('field_key' => isset($file['field_key']) ? $file['field_key'] : NULL, 'original_name' => $file['original_name'], 'mime_type' => $file['mime_type'], 'file_size' => $file['file_size']); }, $uploaded['files']);
            $saved = $this->session->userdata('warga_demo_requests');
            if (!is_array($saved)) $saved = array();
            array_unshift($saved, $created);
            $this->session->set_userdata('warga_demo_requests', $saved);
            return array('success' => TRUE, 'id' => $id);
        }

        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database layanan warga sedang tidak tersedia. Silakan coba lagi.');
        $this->load->model('Auth_model');
        $institution = $this->institution_for_context($user);
        $institutionLower = function_exists('mb_strtolower') ? mb_strtolower($institution, 'UTF-8') : strtolower($institution);
        if (empty($user['id']) || !$this->Auth_model->citizen_is_verified((int) $user['id'], isset($user['village_id']) ? $user['village_id'] : '')) {
            return array('success' => FALSE, 'message' => 'Akun belum terverifikasi sebagai penduduk aktif ' . $institutionLower . ' ini. Permohonan belum dapat dikirim.');
        }
        if (empty($user['village_id'])) return array('success' => FALSE, 'message' => 'Akun belum terhubung ke ' . $institutionLower . '.');
        $service = $this->service_for_user($serviceSlug, $user['village_id']);
        if (!$service) return array('success' => FALSE, 'message' => 'Jenis layanan tidak tersedia untuk ' . $institutionLower . ' Anda.');
        if (isset($service['submission_enabled']) && !$service['submission_enabled']) {
            return array('success' => FALSE, 'message' => !empty($service['availability_note'])
                ? $service['availability_note'] : 'Layanan ini belum dapat diajukan melalui aplikasi warga.');
        }
        $validated = $this->validate_dynamic_fields($service, $formFields);
        if (empty($validated['success'])) return array('success' => FALSE, 'message' => $validated['message']);
        $serviceTypeId = (int) $service['id'];
        if ($serviceTypeId < 1) return array('success' => FALSE, 'message' => 'Relasi layanan belum siap. Sinkronkan katalog lalu coba lagi.');
        $id = warga_uuid();
        $requestCode = 'SDW-' . date('Y') . '-' . strtoupper(substr(str_replace('-', '', $id), 0, 8));
        $uploaded = $this->collect_uploaded_documents($id, $service['form_schema']);
        if (!empty($uploaded['error'])) return array('success' => FALSE, 'message' => $uploaded['error']);
        $now = date('Y-m-d H:i:s');
        $documentMeta = array();
        foreach ($uploaded['files'] as &$file) {
            $file['id'] = warga_uuid();
            $documentMeta[] = array(
                'id' => $file['id'],
                'field_key' => isset($file['field_key']) ? $file['field_key'] : NULL,
                'original_name' => $file['original_name'],
                'mime_type' => $file['mime_type'],
                'file_size' => (int) $file['file_size']
            );
        }
        unset($file);
        // The local SmartDesa inbox needs one self-contained message. The
        // central database remains the source of the actual documents.
        $this->load->model('Community_model');
        $verification = $this->Community_model->workflow($user['village_id']);
        $payload = json_encode(array(
            'request_id' => $id,
            'verification' => $verification,
            'request_code' => $requestCode,
            'service_type_id' => $serviceTypeId,
            'service_slug' => $service['slug'],
            'service_name' => $service['name'],
            'catalog_service_id' => !empty($service['catalog_id']) ? (int) $service['catalog_id'] : NULL,
            'template_key' => !empty($service['template_key']) ? $service['template_key'] : $service['slug'],
            'form_data' => $validated['values'],
            'form_schema' => $service['form_schema'],
            'form_schema_version' => (int) $validated['schema_version'],
            'status' => 'submitted',
            'event_version' => 1,
            'purpose' => $purpose,
            'note' => $note,
            'citizen_name' => isset($user['name']) ? $user['name'] : '',
            'citizen_phone' => isset($user['phone']) ? $user['phone'] : '',
            'citizen_local_key' => isset($user['local_citizen_key']) ? (string) $user['local_citizen_key'] : '',
            'document_count' => count($documentMeta),
            'documents' => $documentMeta,
            'submitted_at' => $now
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Data permohonan belum dapat diproses.');
        }
        $requestRow = array('id' => $id, 'request_code' => $requestCode, 'citizen_user_id' => (int) $user['id'], 'village_id' => $user['village_id'], 'service_type_id' => $serviceTypeId, 'catalog_service_id' => !empty($service['catalog_id']) ? (int) $service['catalog_id'] : NULL, 'form_schema_version' => (int) $validated['schema_version'], 'status' => 'submitted', 'event_version' => 1, 'payload_json' => $payload, 'local_sync_status' => 'pending', 'submitted_at' => $now);
        $this->db->trans_start();
        $this->db->insert('service_requests', $requestRow);
        $this->db->insert('request_status_history', array('request_id' => $id, 'to_status' => 'submitted', 'note' => 'Permohonan diajukan warga.', 'actor_id' => (int) $user['id']));
        foreach ($uploaded['files'] as $file) $this->db->insert('request_documents', array('id' => $file['id'], 'request_id' => $id, 'field_key' => isset($file['field_key']) ? $file['field_key'] : NULL, 'original_name' => $file['original_name'], 'stored_name' => $file['stored_name'], 'storage_path' => $file['storage_path'], 'mime_type' => $file['mime_type'], 'file_size' => $file['file_size'], 'uploaded_by' => (int) $user['id']));
        $this->db->insert('sync_messages', array('id' => warga_uuid(), 'village_id' => $user['village_id'], 'aggregate_type' => 'service_request', 'aggregate_id' => $id, 'direction' => 'cloud_to_local', 'operation' => 'upsert', 'event_version' => 1, 'payload_json' => $payload, 'status' => 'pending', 'idempotency_key' => 'request:' . $id));
        $roles = $verification['sekdes'] ? array('sekdes') : ($verification['kades'] ? array('kepala-desa') : array());
        $this->Community_model->notify_staff($user['village_id'], 'Permohonan surat baru',
            'Ada permohonan surat menunggu pemeriksaan.', 'petugas/permohonan/'.$id, $roles, $id);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Permohonan belum dapat disimpan.');
        }
        return array('success' => TRUE, 'id' => $id);
    }

    /**
     * Send a revision back through the same request ID. The history and
     * citizen-visible URL remain stable; only the submitted payload, files,
     * status, and monotonic event version advance.
     */
    public function resubmit($id, array $user, array $data)
    {
        $id = trim((string) $id);
        if ($id === '') return array('success' => FALSE, 'message' => 'Permohonan tidak valid.');
        $formFields = isset($data['form_fields']) && is_array($data['form_fields']) ? $data['form_fields'] : array();
        $purpose = trim((string) ($data['purpose'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        $textValidation = $this->validate_request_text($purpose, $note);
        if (empty($textValidation['success'])) return $textValidation;

        if (warga_demo_mode()) {
            $request = $this->find_demo_request($id);
            if (!$request || (int) $request['citizen_user_id'] !== (int) $user['id']) return array('success' => FALSE, 'message' => 'Permohonan tidak ditemukan.');
            if ((string) $request['status'] !== 'revision') return array('success' => FALSE, 'message' => 'Permohonan ini tidak sedang menunggu perbaikan.');
            $overrides = $this->session->userdata('warga_demo_request_overrides');
            if (!is_array($overrides)) $overrides = array();
            $overrides[$id] = array('status' => 'submitted', 'updated_at' => date('Y-m-d H:i:s'), 'purpose' => $purpose, 'note' => $note, 'form_data' => $formFields);
            $this->session->set_userdata('warga_demo_request_overrides', $overrides);
            return array('success' => TRUE, 'id' => $id);
        }
        if (!warga_database_available()) return array('success' => FALSE, 'message' => 'Database layanan warga sedang tidak tersedia. Silakan coba lagi.');
        $this->load->model('Auth_model');
        $institution = $this->institution_for_context($user);
        $institutionLower = function_exists('mb_strtolower') ? mb_strtolower($institution, 'UTF-8') : strtolower($institution);
        if (empty($user['id']) || !$this->Auth_model->citizen_is_verified((int) $user['id'], isset($user['village_id']) ? $user['village_id'] : '')) {
            return array('success' => FALSE, 'message' => 'Akun belum terverifikasi sebagai penduduk aktif ' . $institutionLower . ' ini.');
        }
        $request = $this->find_for_user($id, (int) $user['id']);
        if (!$request || (string) $request['status'] !== 'revision') return array('success' => FALSE, 'message' => 'Permohonan tidak ditemukan atau belum meminta perbaikan.');
        $service = $this->service_for_user((string) $request['service_slug'], $request['village_id']);
        if (!$service) return array('success' => FALSE, 'message' => 'Jenis layanan sudah tidak tersedia di ' . $institutionLower . '. Hubungi petugas ' . $institutionLower . '.');
        if (isset($service['submission_enabled']) && !$service['submission_enabled']) return array('success' => FALSE, 'message' => !empty($service['availability_note']) ? $service['availability_note'] : 'Layanan ini tidak menerima pengajuan warga.');
        $validated = $this->validate_dynamic_fields($service, $formFields);
        if (empty($validated['success'])) return array('success' => FALSE, 'message' => $validated['message']);

        $existingDocs = $this->db->where('request_id', $id)->order_by('created_at', 'ASC')->get('request_documents')->result_array();
        $existingCounts = array();
        foreach ($existingDocs as $doc) {
            $key = trim((string) ($doc['field_key'] ?? ''));
            if ($key !== '') $existingCounts[$key] = isset($existingCounts[$key]) ? $existingCounts[$key] + 1 : 1;
        }
        $uploaded = $this->collect_uploaded_documents($id, $service['form_schema'], $existingCounts);
        if (!empty($uploaded['error'])) return array('success' => FALSE, 'message' => $uploaded['error']);
        $replaceKeys = array_fill_keys(isset($uploaded['replace_field_keys']) ? $uploaded['replace_field_keys'] : array(), TRUE);
        $retainedDocs = array_values(array_filter($existingDocs, function ($doc) use ($replaceKeys) {
            $key = trim((string) ($doc['field_key'] ?? ''));
            return $key === '' || !isset($replaceKeys[$key]);
        }));
        if (count($retainedDocs) + count($uploaded['files']) > 10) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Maksimal sepuluh berkas dapat disimpan pada satu permohonan.');
        }

        $oldPayload = json_decode((string) ($request['payload_json'] ?? ''), TRUE);
        if (!is_array($oldPayload)) $oldPayload = array();
        $currentEventVersion = max(1, (int) ($request['event_version'] ?? 1));
        $eventVersion = $currentEventVersion + 1;
        $now = date('Y-m-d H:i:s');
        $documentMeta = array();
        foreach ($retainedDocs as $doc) $documentMeta[] = array(
            'id' => (string) $doc['id'], 'field_key' => $doc['field_key'], 'original_name' => $doc['original_name'],
            'mime_type' => $doc['mime_type'], 'file_size' => (int) $doc['file_size']
        );
        foreach ($uploaded['files'] as &$file) {
            $file['id'] = warga_uuid();
            $documentMeta[] = array('id' => $file['id'], 'field_key' => $file['field_key'], 'original_name' => $file['original_name'], 'mime_type' => $file['mime_type'], 'file_size' => (int) $file['file_size']);
        }
        unset($file);
        $oldPayload['request_id'] = $id;
        $oldPayload['form_data'] = $validated['values'];
        $oldPayload['form_schema'] = $service['form_schema'];
        $oldPayload['form_schema_version'] = (int) $validated['schema_version'];
        $oldPayload['purpose'] = $purpose;
        $oldPayload['note'] = $note;
        $oldPayload['status'] = 'submitted';
        $oldPayload['event_version'] = $eventVersion;
        $oldPayload['documents'] = $documentMeta;
        $oldPayload['document_count'] = count($documentMeta);
        $oldPayload['submitted_at'] = $now;
        $payload = json_encode($oldPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Data perbaikan belum dapat diproses.');
        }

        $this->load->model('Community_model');
        $verification = $this->Community_model->workflow($request['village_id']);
        if (!$this->db->trans_begin()) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Perbaikan permohonan belum dapat dimulai. Silakan coba lagi.');
        }
        $this->db->where(array(
            'id' => $id,
            'citizen_user_id' => (int) $user['id'],
            'status' => 'revision',
            'event_version' => $currentEventVersion
        ))
            ->update('service_requests', array('status' => 'submitted', 'event_version' => $eventVersion, 'form_schema_version' => (int) $validated['schema_version'], 'payload_json' => $payload, 'local_sync_status' => 'pending', 'local_synced_at' => NULL, 'submitted_at' => $now));
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Permohonan berubah karena diproses pengguna lain. Muat ulang halaman.');
        }
        foreach ($existingDocs as $doc) {
            $key = trim((string) ($doc['field_key'] ?? ''));
            if (isset($replaceKeys[$key])) $this->db->where('id', (string) $doc['id'])->delete('request_documents');
        }
        foreach ($uploaded['files'] as $file) $this->db->insert('request_documents', array('id' => $file['id'], 'request_id' => $id, 'field_key' => $file['field_key'], 'original_name' => $file['original_name'], 'stored_name' => $file['stored_name'], 'storage_path' => $file['storage_path'], 'mime_type' => $file['mime_type'], 'file_size' => $file['file_size'], 'uploaded_by' => (int) $user['id']));
        $this->db->insert('request_status_history', array('request_id' => $id, 'from_status' => 'revision', 'to_status' => 'submitted', 'note' => 'Perbaikan permohonan dikirim ulang oleh warga.', 'actor_id' => (int) $user['id']));
        $this->db->insert('sync_messages', array('id' => warga_uuid(), 'village_id' => $request['village_id'], 'aggregate_type' => 'service_request', 'aggregate_id' => $id, 'direction' => 'cloud_to_local', 'operation' => 'upsert', 'event_version' => $eventVersion, 'payload_json' => $payload, 'status' => 'pending', 'idempotency_key' => 'request-resubmit:' . $id . ':' . $eventVersion . ':' . bin2hex(random_bytes(5))));
        $roles = !empty($verification['sekdes']) ? array('sekdes') : (!empty($verification['kades']) ? array('kepala-desa') : array());
        $this->Community_model->notify_staff($request['village_id'], 'Perbaikan permohonan surat', 'Warga telah mengirim perbaikan permohonan.', 'petugas/permohonan/' . $id, $roles, $id);
        if (!$this->db->trans_status()) {
            $this->db->trans_rollback();
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Perbaikan permohonan belum dapat disimpan.');
        }
        if (!$this->db->trans_commit()) {
            $this->cleanup_paths(isset($uploaded['paths']) ? $uploaded['paths'] : array());
            return array('success' => FALSE, 'message' => 'Perbaikan permohonan belum dapat disimpan.');
        }
        foreach ($existingDocs as $doc) {
            $key = trim((string) ($doc['field_key'] ?? ''));
            if (isset($replaceKeys[$key]) && !empty($doc['storage_path']) && is_file($doc['storage_path'])) @unlink($doc['storage_path']);
        }
        return array('success' => TRUE, 'id' => $id);
    }

    public function documents_for_user($requestId, $userId)
    {
        if (warga_demo_mode() || !warga_database_available()) return array();
        return $this->db->select('d.id,d.field_key,d.original_name,d.mime_type,d.file_size')->from('request_documents d')->join('service_requests r', 'r.id=d.request_id')
            ->where(array('d.request_id' => (string) $requestId, 'r.citizen_user_id' => (int) $userId))->order_by('d.created_at', 'ASC')->get()->result_array();
    }

    public function official_document_for_user($requestId, $userId)
    {
        if (warga_demo_mode() || !warga_database_available()) return NULL;
        return $this->db->select('r.document_path, r.document_sha256, r.local_reference')->from('service_requests r')
            ->where(array('r.id' => (string) $requestId, 'r.citizen_user_id' => (int) $userId, 'r.status' => 'issued'))
            ->where('r.document_sha256 IS NOT NULL', NULL, FALSE)->get()->row_array();
    }

    public function official_html_for_user($requestId, $userId)
    {
        if (warga_demo_mode() || !warga_database_available()) return NULL;
        return $this->db->select('r.document_path, r.document_sha256, r.local_reference, r.document_format')->from('service_requests r')
            ->where(array('r.id' => (string) $requestId, 'r.citizen_user_id' => (int) $userId, 'r.status' => 'issued', 'r.document_format' => 'html'))
            ->where('r.document_sha256 IS NOT NULL', NULL, FALSE)->get()->row_array();
    }

}
