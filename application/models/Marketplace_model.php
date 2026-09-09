<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data access for Pasar Digital.
 *
 * Product management remains scoped to the seller's village, while published
 * products and their contact details form a public, cross-village catalogue.
 * Uploaded images stay in private storage and are streamed through the
 * controller only after the product visibility check.
 */
class Marketplace_model extends CI_Model
{
    private $tableReady = NULL;

    private function ready()
    {
        if ($this->tableReady !== NULL) return $this->tableReady;
        if (warga_demo_mode()) return $this->tableReady = TRUE;
        if (!warga_database_available()) return $this->tableReady = FALSE;
        foreach (array('marketplace_categories', 'marketplace_stores', 'marketplace_products', 'marketplace_product_images') as $table) {
            if (!$this->db->table_exists($table)) return $this->tableReady = FALSE;
        }
        return $this->tableReady = TRUE;
    }

    public function is_ready()
    {
        return (bool) $this->ready();
    }

    private function user_village(array $user)
    {
        return trim((string) ($user['village_id'] ?? ''));
    }

    /**
     * Product management is available to verified warga and to the two
     * village operators who administer services. Other administrative roles
     * are intentionally excluded until a separate marketplace policy exists.
     */
    public function can_manage(array $user)
    {
        if ($this->user_village($user) === '' || empty($user['id'])) return FALSE;
        $role = trim((string) ($user['role_slug'] ?? ''));
        if (in_array($role, array('sekdes', 'kepala-desa'), TRUE)) return TRUE;
        if ($role !== 'warga') return FALSE;
        if (warga_demo_mode()) return TRUE;
        $this->load->model('Auth_model');
        return $this->Auth_model->citizen_is_verified((int) $user['id'], $this->user_village($user));
    }

    public function categories(array $user = array())
    {
        if (warga_demo_mode()) return $this->demo_categories();
        if (!$this->ready()) return array();
        return $this->db->select('id,slug,name,sort_order')
            ->where('is_active', 1)->order_by('sort_order', 'ASC')->order_by('name', 'ASC')
            ->get('marketplace_categories')->result_array();
    }

    public function category($id)
    {
        $id = (int) $id;
        if ($id < 1) return NULL;
        if (warga_demo_mode()) {
            foreach ($this->demo_categories() as $category) if ((int) $category['id'] === $id) return $category;
            return NULL;
        }
        if (!$this->ready()) return NULL;
        return $this->db->where(array('id' => $id, 'is_active' => 1))->limit(1)->get('marketplace_categories')->row_array();
    }

    public function store_for_user(array $user)
    {
        $villageId = $this->user_village($user);
        $userId = (int) ($user['id'] ?? 0);
        if ($villageId === '' || $userId < 1) return NULL;
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            if (isset($state['stores'][(string) $userId])) return $this->decorate_store($state['stores'][(string) $userId]);
            return NULL;
        }
        if (!$this->ready()) return NULL;
        $row = $this->db->where(array('village_id' => $villageId, 'owner_user_id' => $userId, 'is_active' => 1))
            ->limit(1)->get('marketplace_stores')->row_array();
        return $row ? $this->decorate_store($row) : NULL;
    }

    public function save_store(array $user, array $data)
    {
        if (!$this->can_manage($user)) return array('success' => FALSE, 'message' => 'Akses mengelola toko ditolak.');
        $villageId = $this->user_village($user);
        $userId = (int) $user['id'];
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $whatsapp = $this->normalize_phone($data['whatsapp'] ?? '');
        $phone = $this->normalize_phone($data['phone'] ?? '');
        $address = trim((string) ($data['address'] ?? ''));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 160) return array('success' => FALSE, 'message' => 'Nama toko wajib diisi (maksimal 160 karakter).');
        if ($description !== '' && mb_strlen($description, 'UTF-8') > 1000) return array('success' => FALSE, 'message' => 'Deskripsi toko maksimal 1.000 karakter.');
        if ($whatsapp === FALSE || $phone === FALSE) return array('success' => FALSE, 'message' => 'Nomor telepon/WhatsApp belum valid.');
        if ($whatsapp === '' && $phone === '') return array('success' => FALSE, 'message' => 'Isi minimal satu nomor WhatsApp atau telepon agar pembeli dapat menghubungi Anda.');
        if ($address !== '' && mb_strlen($address, 'UTF-8') > 255) return array('success' => FALSE, 'message' => 'Alamat toko maksimal 255 karakter.');

        $values = array('village_id' => $villageId, 'owner_user_id' => $userId, 'name' => $name,
            'description' => $description !== '' ? $description : NULL,
            'whatsapp' => $whatsapp !== '' ? $whatsapp : NULL,
            'phone' => $phone !== '' ? $phone : NULL,
            'address' => $address !== '' ? $address : NULL, 'is_active' => 1);
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            $existing = isset($state['stores'][(string) $userId]) ? $state['stores'][(string) $userId] : array();
            $values['id'] = (string) ($existing['id'] ?? warga_uuid());
            $values['created_at'] = (string) ($existing['created_at'] ?? date('Y-m-d H:i:s'));
            $values['updated_at'] = date('Y-m-d H:i:s');
            $state['stores'][(string) $userId] = array_merge($existing, $values);
            $this->save_demo_state($state);
            return array('success' => TRUE, 'id' => $values['id'], 'store' => $this->decorate_store($state['stores'][(string) $userId]));
        }
        if (!$this->ready()) return array('success' => FALSE, 'message' => 'Fitur Pasar Digital belum diaktifkan pada database. Jalankan migrasi marketplace terlebih dahulu.');
        $existing = $this->db->where(array('village_id' => $villageId, 'owner_user_id' => $userId))->limit(1)->get('marketplace_stores')->row_array();
        if ($existing) {
            $ok = $this->db->where('id', $existing['id'])->update('marketplace_stores', $values);
            $id = $existing['id'];
        } else {
            $id = warga_uuid();
            $values['id'] = $id;
            $ok = $this->db->insert('marketplace_stores', $values);
        }
        return $ok ? array('success' => TRUE, 'id' => $id, 'store' => $this->store_for_user($user)) : array('success' => FALSE, 'message' => 'Identitas toko belum dapat disimpan.');
    }

    /**
     * Return a paginated, village-scoped product list. Staff and the seller
     * can see their own drafts; everyone else sees published products only.
     */
    public function products(array $user, array $filters = array())
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(48, (int) ($filters['per_page'] ?? 12)));
        $q = trim((string) ($filters['q'] ?? ''));
        $category = (int) ($filters['category_id'] ?? 0);
        $sort = trim((string) ($filters['sort'] ?? 'newest'));
        if (!in_array($sort, array('newest', 'price_low', 'price_high', 'name'), TRUE)) $sort = 'newest';
        $publicAll = !empty($filters['public_all']);
        $onlyOwn = !empty($filters['only_own']);
        $villageId = $this->user_village($user);
        $canManage = $this->can_manage($user);
        if (warga_demo_mode()) {
            $rows = array_values(array_filter($this->demo_products(), function ($row) use ($villageId, $q, $category, $canManage, $user, $publicAll, $onlyOwn) {
                if (!$publicAll && (string) ($row['village_id'] ?? '') !== $villageId) return FALSE;
                if ($onlyOwn && (int) ($row['seller_user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) return FALSE;
                $visible = (string) ($row['status'] ?? '') === 'published' || ($canManage && (int) ($row['seller_user_id'] ?? 0) === (int) ($user['id'] ?? 0));
                if (!$onlyOwn && !$visible) return FALSE;
                if ($category > 0 && (int) ($row['category_id'] ?? 0) !== $category) return FALSE;
                if ($q !== '' && stripos((string) ($row['name'] . ' ' . ($row['description'] ?? '') . ' ' . ($row['category_name'] ?? '')), $q) === FALSE) return FALSE;
                return TRUE;
            }));
            usort($rows, function ($a, $b) use ($sort) {
                if ($sort === 'price_low' || $sort === 'price_high') {
                    $compare = ((float) ($a['price'] ?? 0)) <=> ((float) ($b['price'] ?? 0));
                    return $sort === 'price_high' ? -$compare : $compare;
                }
                if ($sort === 'name') return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
            });
            $total = count($rows);
            $items = array_slice($rows, ($page - 1) * $perPage, $perPage);
            $items = $this->attach_review_summaries($items);
            return array('items' => array_map(array($this, 'decorate_product'), $items), 'total' => $total,
                'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'per_page' => $perPage,
                'filters' => array('q' => $q, 'category_id' => $category, 'sort' => $sort));
        }
        if (!$this->ready()) return array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage,
            'filters' => array('q' => $q, 'category_id' => $category, 'sort' => $sort), 'ready' => FALSE);
        if ($villageId === '' && !$publicAll) return array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage, 'filters' => array('q' => $q, 'category_id' => $category));
        $this->db->from('marketplace_products p')->join('marketplace_categories c', 'c.id=p.category_id')
            ->join('marketplace_stores s', 's.id=p.store_id');
        if (!$publicAll) $this->db->where('p.village_id', $villageId);
        if ($onlyOwn) $this->db->where('p.seller_user_id', (int) ($user['id'] ?? 0));
        else $this->apply_product_visibility($user, $canManage);
        if ($q !== '') $this->db->group_start()->like('p.name', $q)->or_like('p.description', $q)->or_like('c.name', $q)->group_end();
        if ($category > 0) $this->db->where('p.category_id', $category);
        $total = (int) $this->db->count_all_results();
        $this->db->select('p.*,c.slug AS category_slug,c.name AS category_name,s.name AS store_name,s.whatsapp AS store_whatsapp,s.phone AS store_phone,s.address AS store_address,v.name AS village_name');
        $this->db->from('marketplace_products p')->join('marketplace_categories c', 'c.id=p.category_id')
            ->join('marketplace_stores s', 's.id=p.store_id')
            ->join('village_tenants v', 'v.id=p.village_id', 'left');
        if (!$publicAll) $this->db->where('p.village_id', $villageId);
        if ($onlyOwn) $this->db->where('p.seller_user_id', (int) ($user['id'] ?? 0));
        else $this->apply_product_visibility($user, $canManage);
        if ($q !== '') $this->db->group_start()->like('p.name', $q)->or_like('p.description', $q)->or_like('c.name', $q)->group_end();
        if ($category > 0) $this->db->where('p.category_id', $category);
        if ($sort === 'price_low') $this->db->order_by('p.price', 'ASC');
        elseif ($sort === 'price_high') $this->db->order_by('p.price', 'DESC');
        elseif ($sort === 'name') $this->db->order_by('p.name', 'ASC');
        else $this->db->order_by('p.updated_at', 'DESC');
        $rows = $this->db->order_by('p.name', 'ASC')->limit($perPage, ($page - 1) * $perPage)->get()->result_array();
        $rows = $this->attach_images($rows);
        $rows = $this->attach_review_summaries($rows);
        return array('items' => array_map(array($this, 'decorate_product'), $rows), 'total' => $total,
            'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'per_page' => $perPage,
            'filters' => array('q' => $q, 'category_id' => $category, 'sort' => $sort), 'ready' => TRUE);
    }

    public function product($id, array $user, $publicAll = FALSE)
    {
        $id = trim((string) $id);
        if ($id === '' || (!$publicAll && $this->user_village($user) === '')) return NULL;
        $canManage = $this->can_manage($user);
        if (warga_demo_mode()) {
            foreach ($this->demo_products() as $row) {
                if ((string) $row['id'] !== $id || (!$publicAll && (string) $row['village_id'] !== $this->user_village($user))) continue;
                if ((string) $row['status'] !== 'published' && !($canManage && (int) $row['seller_user_id'] === (int) $user['id'])) return NULL;
                $row = $this->decorate_product($row);
                return $this->attach_product_reviews($row);
            }
            return NULL;
        }
        if (!$this->ready()) return NULL;
        $this->db->select('p.*,c.slug AS category_slug,c.name AS category_name,s.name AS store_name,s.description AS store_description,s.whatsapp AS store_whatsapp,s.phone AS store_phone,s.address AS store_address,s.owner_user_id,v.name AS village_name')
            ->from('marketplace_products p')->join('marketplace_categories c', 'c.id=p.category_id')
            ->join('marketplace_stores s', 's.id=p.store_id')
            ->join('village_tenants v', 'v.id=p.village_id', 'left')
            ->where('p.id', $id);
        if (!$publicAll) $this->db->where('p.village_id', $this->user_village($user));
        if (!$canManage) $this->db->where('p.status', 'published');
        else $this->db->group_start()->where('p.status', 'published')->or_group_start()->where('p.seller_user_id', (int) $user['id'])->where_in('p.status', array('draft', 'archived'))->group_end()->group_end();
        $row = $this->db->limit(1)->get()->row_array();
        if (!$row) return NULL;
        $row['images'] = $this->images_for_product($id);
        $row = $this->decorate_product($row);
        return $this->attach_product_reviews($row);
    }

    /**
     * Create or update a product. Uploaded images use input name
     * `product_images[]`; the model accepts JPG, PNG, and WEBP (max 5 MB each,
     * maximum six images), then stores a metadata-free WebP (max 1600 px) and
     * a 640 px card thumbnail. The returned `paths` are for cleanup by callers.
     */
    public function save_product(array $user, array $data, array $files = array())
    {
        if (!$this->can_manage($user)) return array('success' => FALSE, 'message' => 'Akses membuat produk ditolak.');
        $id = trim((string) ($data['id'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $categoryId = (int) ($data['category_id'] ?? 0);
        $price = $this->normalize_price($data['price'] ?? '');
        $stockRaw = trim((string) ($data['stock'] ?? ''));
        $stock = $stockRaw === '' ? NULL : (int) $stockRaw;
        $status = trim((string) ($data['status'] ?? 'published'));
        if (!in_array($status, array('draft', 'published', 'archived'), TRUE)) $status = 'published';
        if ($name === '' || mb_strlen($name, 'UTF-8') > 180) return array('success' => FALSE, 'message' => 'Nama produk wajib diisi (maksimal 180 karakter).');
        if ($description !== '' && mb_strlen($description, 'UTF-8') > 5000) return array('success' => FALSE, 'message' => 'Deskripsi produk maksimal 5.000 karakter.');
        if ($categoryId < 1 || !$this->category($categoryId)) return array('success' => FALSE, 'message' => 'Pilih kategori produk yang tersedia.');
        if ($price === FALSE) return array('success' => FALSE, 'message' => 'Harga produk harus berupa angka nol atau lebih.');
        if ($stock !== NULL && ($stock < 0 || $stock > 4294967295)) return array('success' => FALSE, 'message' => 'Stok produk belum valid.');
        $store = $this->store_for_user($user);
        if (!$store) {
            $storeResult = $this->save_store($user, array('name' => trim((string) ($user['name'] ?? 'Toko Warga')), 'whatsapp' => $user['phone'] ?? '', 'phone' => $user['phone'] ?? ''));
            if (empty($storeResult['success'])) return $storeResult;
            $store = $storeResult['store'];
        }
        $existing = NULL;
        if ($id !== '') $existing = $this->product_owned($id, $user);
        if ($id !== '' && !$existing) return array('success' => FALSE, 'message' => 'Produk tidak ditemukan atau bukan milik Anda.');
        $productId = $existing ? $id : warga_uuid();
        $upload = $this->prepare_images($productId, $files, $existing ? count($existing['images'] ?? array()) : 0);
        if (!empty($upload['error'])) return array('success' => FALSE, 'message' => $upload['error']);
        if (!$existing && empty($upload['files'])) return array('success' => FALSE, 'message' => 'Tambahkan minimal satu foto produk.');
        $values = array('village_id' => $this->user_village($user), 'store_id' => $store['id'], 'seller_user_id' => (int) $user['id'],
            'category_id' => $categoryId, 'name' => $name, 'description' => $description !== '' ? $description : NULL,
            'price' => $price, 'stock' => $stock, 'status' => $status);
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            $old = $existing ?: array();
            $values['id'] = $productId; $values['created_at'] = $old['created_at'] ?? date('Y-m-d H:i:s'); $values['updated_at'] = date('Y-m-d H:i:s');
            $category = $this->category($categoryId); $values['category_slug'] = $category['slug']; $values['category_name'] = $category['name'];
            $values['store_name'] = $store['name']; $values['store_whatsapp'] = $store['whatsapp']; $values['store_phone'] = $store['phone']; $values['images'] = $old['images'] ?? array();
            foreach ($upload['files'] as $image) $values['images'][] = $image;
            $found = FALSE;
            foreach ($state['products'] as $index => $row) if ((string) $row['id'] === $productId) { $state['products'][$index] = array_merge($row, $values); $found = TRUE; break; }
            if (!$found) $state['products'][] = $values;
            $this->save_demo_state($state);
            return array('success' => TRUE, 'id' => $productId, 'paths' => $upload['paths'], 'product' => $this->decorate_product($values));
        }
        if (!$this->db->trans_begin()) { $this->cleanup_paths($upload['paths']); return array('success' => FALSE, 'message' => 'Produk belum dapat disimpan.'); }
        if ($existing) $ok = $this->db->where('id', $productId)->update('marketplace_products', $values);
        else { $values['id'] = $productId; $ok = $this->db->insert('marketplace_products', $values); }
        if (!$ok) { $this->db->trans_rollback(); $this->cleanup_paths($upload['paths']); return array('success' => FALSE, 'message' => 'Produk belum dapat disimpan.'); }
        $sort = (int) ($existing ? count($existing['images']) : 0);
        foreach ($upload['files'] as $image) {
            $this->db->insert('marketplace_product_images', array('product_id' => $productId, 'original_name' => $image['original_name'], 'stored_name' => $image['stored_name'], 'storage_path' => $image['storage_path'], 'mime_type' => $image['mime_type'], 'file_size' => $image['file_size'], 'sort_order' => $sort++, 'is_cover' => $sort === 1 ? 1 : 0));
        }
        if (!$this->db->trans_status()) { $this->db->trans_rollback(); $this->cleanup_paths($upload['paths']); return array('success' => FALSE, 'message' => 'Produk belum dapat disimpan.'); }
        $this->db->trans_commit();
        return array('success' => TRUE, 'id' => $productId, 'paths' => $upload['paths'], 'product' => $this->product($productId, $user));
    }

    public function product_owned($id, array $user)
    {
        $id = trim((string) $id);
        if ($id === '' || $this->user_village($user) === '') return NULL;
        if (warga_demo_mode()) {
            foreach ($this->demo_products() as $row) if ((string) $row['id'] === $id && (string) $row['village_id'] === $this->user_village($user) && (int) $row['seller_user_id'] === (int) $user['id']) return $this->decorate_product($row);
            return NULL;
        }
        if (!$this->ready()) return NULL;
        $row = $this->db->where(array('id' => $id, 'village_id' => $this->user_village($user), 'seller_user_id' => (int) $user['id']))->limit(1)->get('marketplace_products')->row_array();
        if (!$row) return NULL;
        $row['images'] = $this->images_for_product($id);
        return $this->decorate_product($row);
    }

    public function archive_product($id, array $user)
    {
        $product = $this->product_owned($id, $user);
        if (!$product || !$this->can_manage($user)) return FALSE;
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            foreach ($state['products'] as $index => $row) if ((string) $row['id'] === (string) $id) { $state['products'][$index]['status'] = 'archived'; $state['products'][$index]['updated_at'] = date('Y-m-d H:i:s'); }
            $this->save_demo_state($state); return TRUE;
        }
        if (!$this->ready()) return FALSE;
        return (bool) $this->db->where(array('id' => $id, 'village_id' => $this->user_village($user), 'seller_user_id' => (int) $user['id']))->update('marketplace_products', array('status' => 'archived'));
    }

    /**
     * Permanently remove a product and its dependent rows. Sellers may remove
     * their own products; village marketplace staff may remove any product in
     * their village. Uploaded files are deleted only after the DB commit and
     * only when their resolved paths remain inside this marketplace storage.
     */
    public function delete_product($id, array $user)
    {
        $id = trim((string) $id);
        $villageId = $this->user_village($user);
        if ($id === '' || $villageId === '' || !$this->can_manage($user)) return FALSE;
        $role = trim((string) ($user['role_slug'] ?? ''));
        $staffDelete = in_array($role, array('sekdes', 'kepala-desa'), TRUE);

        if (warga_demo_mode()) {
            $state = $this->demo_state();
            $paths = array(); $found = FALSE; $allowed = FALSE;
            foreach ($state['products'] as $row) {
                if ((string) ($row['id'] ?? '') !== $id || (string) ($row['village_id'] ?? '') !== $villageId) continue;
                $found = TRUE;
                $allowed = $staffDelete || (int) ($row['seller_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
                foreach (($row['images'] ?? array()) as $image) $paths = array_merge($paths, $this->image_paths($image));
                break;
            }
            if (!$found || !$allowed) return FALSE;
            $state['products'] = array_values(array_filter($state['products'], function ($row) use ($id) {
                return (string) ($row['id'] ?? '') !== $id;
            }));
            if (isset($state['reviews'][$id])) unset($state['reviews'][$id]);
            $this->save_demo_state($state);
            $this->cleanup_product_paths($paths, $id);
            return TRUE;
        }
        if (!$this->ready()) return FALSE;

        $product = $this->db->select('id,seller_user_id')->where(array('id' => $id, 'village_id' => $villageId))
            ->limit(1)->get('marketplace_products')->row_array();
        if (!$product || (!$staffDelete && (int) $product['seller_user_id'] !== (int) ($user['id'] ?? 0))) return FALSE;
        $images = $this->db->select('stored_name,storage_path')->where('product_id', $id)->get('marketplace_product_images')->result_array();
        $paths = array(); foreach ($images as $image) $paths = array_merge($paths, $this->image_paths($image));
        if (!$this->db->trans_begin()) return FALSE;
        $ok = $this->db->where('product_id', $id)->delete('marketplace_product_images');
        if ($ok && $this->db->table_exists('marketplace_product_reviews')) {
            $ok = $this->db->where('product_id', $id)->delete('marketplace_product_reviews');
        }
        if ($ok) $ok = $this->db->where(array('id' => $id, 'village_id' => $villageId))->delete('marketplace_products');
        if (!$ok || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        $this->cleanup_product_paths($paths, $id);
        return TRUE;
    }

    public function image_for_user($imageId, array $user, $publicAll = FALSE)
    {
        $imageId = (int) $imageId;
        if ($imageId < 1 || (!$publicAll && $this->user_village($user) === '')) return NULL;
        if (warga_demo_mode()) {
            foreach ($this->demo_products() as $product) {
                if (!$publicAll && (string) ($product['village_id'] ?? '') !== $this->user_village($user)) continue;
                $visible = (string) ($product['status'] ?? '') === 'published'
                    || ($this->can_manage($user) && (int) ($product['seller_user_id'] ?? 0) === (int) ($user['id'] ?? 0));
                if (!$visible) continue;
                foreach (($product['images'] ?? array()) as $image) {
                    if ((int) ($image['id'] ?? 0) !== $imageId) continue;
                    // Demo records do not have a joined product row like the
                    // database query below, so carry visibility metadata into
                    // the image endpoint before it decides cacheability.
                    $image['status'] = (string) ($product['status'] ?? 'published');
                    $image['seller_user_id'] = (int) ($product['seller_user_id'] ?? 0);
                    $image['village_id'] = (string) ($product['village_id'] ?? '');
                    $image['product_updated_at'] = (string) ($product['updated_at'] ?? '');
                    return $image;
                }
            }
            return NULL;
        }
        if (!$this->ready()) return NULL;
        $this->db->select('i.*,p.village_id,p.status,p.seller_user_id,p.updated_at AS product_updated_at')->from('marketplace_product_images i')->join('marketplace_products p', 'p.id=i.product_id')
            ->where('i.id', $imageId);
        if (!$publicAll) $this->db->where('p.village_id', $this->user_village($user));
        $row = $this->db->limit(1)->get()->row_array();
        if (!$row) return NULL;
        if ((string) $row['status'] !== 'published' && (!$this->can_manage($user) || (int) $row['seller_user_id'] !== (int) $user['id'])) return NULL;
        return $row;
    }

    /** Resolve the full-size or thumbnail path without exposing storage paths. */
    public function image_variant_path(array $image, $variant = 'full')
    {
        $path = trim((string) ($image['storage_path'] ?? ''));
        if ($path === '') return '';
        if ($variant !== 'thumb') return $path;
        $storedThumb = trim((string) ($image['thumbnail_path'] ?? ''));
        if ($storedThumb !== '') return $storedThumb;
        $directory = dirname($path);
        $basename = pathinfo($path, PATHINFO_FILENAME);
        return $directory . DIRECTORY_SEPARATOR . $basename . '-thumb.webp';
    }

    /** Create a missing thumbnail for legacy uploads on first use. */
    public function ensure_thumbnail(array $image)
    {
        $thumbnail = $this->image_variant_path($image, 'thumb');
        if ($thumbnail !== '' && is_file($thumbnail) && (int) @filesize($thumbnail) > 0) return $thumbnail;
        $source = $this->image_variant_path($image, 'full');
        if ($source === '' || !is_file($source) || !function_exists('imagewebp')) return '';
        $root = $this->private_storage_path(); $sourceReal = realpath($source);
        $targetDirectory = $thumbnail !== '' ? realpath(dirname($thumbnail)) : FALSE;
        if ($root === NULL || $sourceReal === FALSE || $targetDirectory === FALSE || is_link($thumbnail)) return '';
        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($sourceReal, $rootPrefix) !== 0 || strpos(rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $rootPrefix) !== 0) return '';
        $thumbnail = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($thumbnail);
        $mime = strtolower(trim((string) ($image['mime_type'] ?? '')));
        if ($mime === '' && function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE); $mime = $finfo ? strtolower(trim((string) @finfo_file($finfo, $source))) : '';
            if ($finfo) finfo_close($finfo);
        }
        if (!in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), TRUE)) return '';
        $created = $this->create_thumbnail_file($source, $mime, $thumbnail);
        if ($created) @chmod($thumbnail, 0640);
        return $created ? $thumbnail : '';
    }

    /** Stable cache token changes when the source image/version changes. */
    public function image_cache_token(array $image)
    {
        // Include publication state so a draft/archived product never reuses
        // the public URL version that may already exist in Cache Storage.
        $parts = array((string) ($image['id'] ?? ''), (string) ($image['stored_name'] ?? ''), (string) ($image['file_size'] ?? ''),
            (string) ($image['status'] ?? ($image['product_status'] ?? 'published')), (string) ($image['product_updated_at'] ?? ''));
        $source = $this->image_variant_path($image, 'full');
        if ($source !== '' && is_file($source)) $parts[] = (string) @filesize($source) . ':' . (string) @filemtime($source);
        return substr(hash('sha256', implode('|', $parts)), 0, 20);
    }

    private function apply_product_visibility(array $user, $canManage)
    {
        if (!$canManage) { $this->db->where('p.status', 'published'); return; }
        $this->db->group_start()->where('p.status', 'published')->or_group_start()->where('p.seller_user_id', (int) $user['id'])->where_in('p.status', array('draft', 'archived'))->group_end()->group_end();
    }

    private function attach_images(array $rows)
    {
        if (!$rows) return $rows;
        $ids = array(); foreach ($rows as $row) $ids[] = $row['id'];
        $images = $this->db->where_in('product_id', $ids)->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get('marketplace_product_images')->result_array();
        $byProduct = array(); foreach ($images as $image) $byProduct[(string) $image['product_id']][] = $image;
        foreach ($rows as &$row) $row['images'] = isset($byProduct[(string) $row['id']]) ? $byProduct[(string) $row['id']] : array();
        unset($row); return $rows;
    }

    private function images_for_product($productId)
    {
        return $this->db->where('product_id', (string) $productId)->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get('marketplace_product_images')->result_array();
    }

    private function decorate_store(array $store)
    {
        $store['whatsapp_url'] = $this->phone_url($store['whatsapp'] ?? '', TRUE);
        $store['phone_url'] = $this->phone_url($store['phone'] ?? ($store['whatsapp'] ?? ''), FALSE);
        return $store;
    }

    private function decorate_product(array $product)
    {
        $product['price_value'] = (float) ($product['price'] ?? 0);
        $product['price_label'] = 'Rp ' . number_format($product['price_value'], 0, ',', '.');
        $product['images'] = isset($product['images']) && is_array($product['images']) ? array_values($product['images']) : array();
        foreach ($product['images'] as &$image) {
            if (isset($image['id'])) {
                if (!isset($image['status'])) $image['status'] = (string) ($product['status'] ?? 'published');
                if (!isset($image['product_updated_at'])) $image['product_updated_at'] = (string) ($product['updated_at'] ?? '');
                $base = site_url('pasar/gambar/' . rawurlencode((string) $image['id']));
                $version = $this->image_cache_token($image);
                $image['url'] = $base . '?v=' . rawurlencode($version);
                $image['thumbnail_url'] = $base . '?variant=thumb&v=' . rawurlencode($version);
            }
        }
        unset($image);
        $phone = (string) ($product['store_whatsapp'] ?? ($product['store_phone'] ?? ''));
        $product['whatsapp_url'] = $this->phone_url($phone, TRUE);
        $product['phone_url'] = $this->phone_url((string) ($product['store_phone'] ?? $phone), FALSE);
        $product['rating_average'] = isset($product['rating_average']) ? round((float) $product['rating_average'], 2) : 0;
        $product['rating_count'] = max(0, (int) ($product['rating_count'] ?? 0));
        return $product;
    }

    /**
     * Return published reviews for one public product. Reviews are optional
     * so an installation that has not run the reviews migration can still
     * use the catalogue normally.
     */
    public function reviews_for_product($productId)
    {
        $productId = trim((string) $productId);
        if ($productId === '') return array();
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            $items = isset($state['reviews'][$productId]) && is_array($state['reviews'][$productId])
                ? $state['reviews'][$productId] : array();
            usort($items, function ($a, $b) {
                return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            });
            return array_slice($items, 0, 50);
        }
        if (!$this->reviews_ready()) return array();
        return $this->db->select('id,product_id,user_id,reviewer_name,rating,comment,created_at,updated_at')
            ->where(array('product_id' => $productId, 'status' => 'published'))
            ->order_by('created_at', 'DESC')->order_by('id', 'DESC')->limit(50)
            ->get('marketplace_product_reviews')->result_array();
    }

    public function review_summary($productId)
    {
        $productId = trim((string) $productId);
        if ($productId === '') return array('rating_average' => 0, 'rating_count' => 0);
        if (warga_demo_mode()) {
            $reviews = $this->reviews_for_product($productId);
            $count = count($reviews);
            $total = 0;
            foreach ($reviews as $review) $total += (int) ($review['rating'] ?? 0);
            return array('rating_average' => $count ? round($total / $count, 2) : 0, 'rating_count' => $count);
        }
        if (!$this->reviews_ready()) return array('rating_average' => 0, 'rating_count' => 0);
        $row = $this->db->select('COUNT(*) AS rating_count, COALESCE(AVG(rating), 0) AS rating_average', FALSE)
            ->where(array('product_id' => $productId, 'status' => 'published'))
            ->limit(1)->get('marketplace_product_reviews')->row_array();
        return array('rating_average' => round((float) ($row['rating_average'] ?? 0), 2), 'rating_count' => (int) ($row['rating_count'] ?? 0));
    }

    /**
     * Save one rating/comment per account and return the normalized review and
     * updated aggregate. Re-submitting updates that user's existing review.
     */
    public function save_review(array $user, $productId, $rating, $comment)
    {
        $productId = trim((string) $productId);
        $userId = (int) ($user['id'] ?? 0);
        $rating = (int) $rating;
        $comment = trim((string) $comment);
        if ($userId < 1) return array('success' => FALSE, 'message' => 'Login diperlukan. Silakan masuk terlebih dahulu untuk memberi rating.');
        if ($productId === '' || !$this->product($productId, array(), TRUE)) return array('success' => FALSE, 'message' => 'Produk yang dinilai tidak ditemukan.');
        if ($rating < 1 || $rating > 5) return array('success' => FALSE, 'message' => 'Pilih rating antara 1 sampai 5 bintang.');
        if ($comment !== '' && mb_strlen($comment, 'UTF-8') < 3) return array('success' => FALSE, 'message' => 'Komentar minimal 3 karakter atau boleh dikosongkan.');
        if (mb_strlen($comment, 'UTF-8') > 1000) return array('success' => FALSE, 'message' => 'Komentar maksimal 1.000 karakter.');
        $reviewer = trim((string) ($user['name'] ?? ($user['username'] ?? 'Warga')));
        if ($reviewer === '') $reviewer = 'Warga';
        $now = date('Y-m-d H:i:s');
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            if (!isset($state['reviews'][$productId]) || !is_array($state['reviews'][$productId])) $state['reviews'][$productId] = array();
            $review = NULL;
            foreach ($state['reviews'][$productId] as $index => $existing) {
                if ((int) ($existing['user_id'] ?? 0) === $userId) {
                    $review = array_merge($existing, array('rating' => $rating, 'comment' => $comment, 'reviewer_name' => $reviewer, 'updated_at' => $now));
                    $state['reviews'][$productId][$index] = $review;
                    break;
                }
            }
            if (!$review) {
                $review = array('id' => function_exists('random_int') ? random_int(100000000, 999999999) : mt_rand(100000000, 999999999), 'product_id' => $productId, 'user_id' => $userId, 'reviewer_name' => $reviewer, 'rating' => $rating, 'comment' => $comment, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now);
                $state['reviews'][$productId][] = $review;
            }
            $this->save_demo_state($state);
            return array('success' => TRUE, 'review' => $review, 'summary' => $this->review_summary($productId));
        }
        if (!$this->reviews_ready()) return array('success' => FALSE, 'message' => 'Fitur rating belum diaktifkan pada database. Jalankan migrasi ulasan Pasar Digital terlebih dahulu.');
        $existing = $this->db->where(array('product_id' => $productId, 'user_id' => $userId))->limit(1)->get('marketplace_product_reviews')->row_array();
        $values = array('reviewer_name' => $reviewer, 'rating' => $rating, 'comment' => $comment, 'status' => 'published', 'updated_at' => $now);
        if ($existing) {
            $ok = $this->db->where('id', $existing['id'])->update('marketplace_product_reviews', $values);
            $reviewId = $existing['id'];
        } else {
            $values['product_id'] = $productId; $values['user_id'] = $userId; $values['created_at'] = $now;
            $ok = $this->db->insert('marketplace_product_reviews', $values);
            $reviewId = $this->db->insert_id();
        }
        if (!$ok) return array('success' => FALSE, 'message' => 'Rating belum dapat disimpan. Coba lagi.');
        $review = $this->db->select('id,product_id,user_id,reviewer_name,rating,comment,created_at,updated_at')->where('id', $reviewId)->limit(1)->get('marketplace_product_reviews')->row_array();
        return array('success' => TRUE, 'review' => $review ?: array_merge($values, array('id' => $reviewId)), 'summary' => $this->review_summary($productId));
    }

    private function attach_product_reviews(array $product)
    {
        $summary = $this->review_summary($product['id'] ?? '');
        $product['rating_average'] = $summary['rating_average'];
        $product['rating_count'] = $summary['rating_count'];
        $product['reviews'] = $this->reviews_for_product($product['id'] ?? '');
        return $product;
    }

    private function attach_review_summaries(array $rows)
    {
        if (!$rows) return $rows;
        $ids = array(); foreach ($rows as $row) $ids[] = (string) ($row['id'] ?? '');
        $byProduct = array();
        if (warga_demo_mode()) {
            $state = $this->demo_state();
            foreach ($ids as $id) {
                $reviews = isset($state['reviews'][$id]) && is_array($state['reviews'][$id]) ? $state['reviews'][$id] : array();
                $total = 0; foreach ($reviews as $review) $total += (int) ($review['rating'] ?? 0);
                $byProduct[$id] = array('rating_average' => $reviews ? round($total / count($reviews), 2) : 0, 'rating_count' => count($reviews));
            }
        } elseif ($this->reviews_ready()) {
            $query = $this->db->select('product_id,COUNT(*) AS rating_count,COALESCE(AVG(rating),0) AS rating_average', FALSE)
                ->where('status', 'published')->where_in('product_id', $ids)
                ->group_by('product_id')->get('marketplace_product_reviews')->result_array();
            foreach ($query as $summary) $byProduct[(string) $summary['product_id']] = array('rating_average' => round((float) $summary['rating_average'], 2), 'rating_count' => (int) $summary['rating_count']);
        }
        foreach ($rows as &$row) {
            $summary = $byProduct[(string) ($row['id'] ?? '')] ?? array('rating_average' => 0, 'rating_count' => 0);
            $row['rating_average'] = $summary['rating_average']; $row['rating_count'] = $summary['rating_count'];
        }
        unset($row);
        return $rows;
    }

    private function reviews_ready()
    {
        return warga_demo_mode() || (warga_database_available() && $this->db->table_exists('marketplace_product_reviews'));
    }

    private function normalize_price($value)
    {
        $value = trim(str_replace(array('Rp', 'rp', ' ', '.'), '', (string) $value));
        $value = str_replace(',', '.', $value);
        if ($value === '' || !preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/', $value)) return FALSE;
        $number = (float) $value;
        return $number >= 0 && $number <= 9999999999999 ? number_format($number, 2, '.', '') : FALSE;
    }

    private function normalize_phone($value)
    {
        $value = trim((string) $value); if ($value === '') return '';
        if (!preg_match('/^[0-9+() .-]+$/', $value)) return FALSE;
        $normalized = preg_replace('/[^0-9+]/', '', $value);
        return preg_match('/^\+?[0-9]{8,15}$/', $normalized) ? $normalized : FALSE;
    }

    private function phone_url($phone, $whatsapp)
    {
        $phone = $this->normalize_phone($phone); if ($phone === FALSE || $phone === '') return '';
        $digits = ltrim($phone, '+');
        if (substr($digits, 0, 1) === '0') $digits = '62' . substr($digits, 1);
        return $whatsapp ? 'https://wa.me/' . $digits : 'tel:+' . $digits;
    }

    private function prepare_images($productId, array $files, $existingCount = 0)
    {
        $entries = array();
        $names = isset($files['name']) && is_array($files['name']) ? $files['name'] : array();
        $types = isset($files['type']) && is_array($files['type']) ? $files['type'] : array();
        $tmp = isset($files['tmp_name']) && is_array($files['tmp_name']) ? $files['tmp_name'] : array();
        $errors = isset($files['error']) && is_array($files['error']) ? $files['error'] : array();
        $sizes = isset($files['size']) && is_array($files['size']) ? $files['size'] : array();
        foreach ($names as $index => $name) if ((int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $entries[] = array('name' => (string) $name, 'type' => (string) ($types[$index] ?? ''), 'tmp_name' => (string) ($tmp[$index] ?? ''), 'error' => (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE), 'size' => (int) ($sizes[$index] ?? 0));
        if ($existingCount + count($entries) > 6) return array('files' => array(), 'paths' => array(), 'error' => 'Maksimal enam gambar dapat digunakan untuk satu produk.');
        if (!$entries) return array('files' => array(), 'paths' => array(), 'error' => NULL);
        if (!function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) return array('files' => array(), 'paths' => array(), 'error' => 'Server belum mendukung optimasi gambar WebP. Aktifkan ekstensi GD dengan dukungan WebP terlebih dahulu.');
        $root = $this->private_storage_path(); if ($root === NULL) return array('files' => array(), 'paths' => array(), 'error' => 'Penyimpanan gambar belum siap.');
        $dir = $root . DIRECTORY_SEPARATOR . 'marketplace' . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        if (!is_dir($dir) && !@mkdir($dir, 0750, TRUE) && !is_dir($dir)) return array('files' => array(), 'paths' => array(), 'error' => 'Folder gambar belum dapat dibuat.');
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : NULL;
        if (!$finfo) return array('files' => array(), 'paths' => array(), 'error' => 'Pemeriksaan gambar belum tersedia pada server.');
        $allowed = array('image/jpeg' => TRUE, 'image/png' => TRUE, 'image/webp' => TRUE); $out = array(); $paths = array();
        try {
            foreach ($entries as $entry) {
                if ($entry['error'] !== UPLOAD_ERR_OK || $entry['size'] < 1 || $entry['size'] > 5 * 1024 * 1024 || !is_uploaded_file($entry['tmp_name'])) throw new RuntimeException('Setiap gambar harus berformat JPG, PNG, atau WEBP dan maksimal 5 MB.');
                $mime = strtolower(trim((string) finfo_file($finfo, $entry['tmp_name'])));
                if (!isset($allowed[$mime])) throw new RuntimeException('Format gambar hanya boleh JPG, PNG, atau WEBP.');
                $dimensions = @getimagesize($entry['tmp_name']);
                if (!is_array($dimensions) || (int) ($dimensions[0] ?? 0) < 1 || (int) ($dimensions[1] ?? 0) < 1
                    || (int) $dimensions[0] > 20000 || (int) $dimensions[1] > 20000
                    || ((int) $dimensions[0] * (int) $dimensions[1]) > 24000000) throw new RuntimeException('Dimensi gambar terlalu besar untuk diproses.');
                $base = $productId . '-' . bin2hex(random_bytes(8));
                $name = $base . '.webp'; $thumbName = $base . '-thumb.webp';
                $destination = $dir . DIRECTORY_SEPARATOR . $name; $thumbDestination = $dir . DIRECTORY_SEPARATOR . $thumbName;
                $optimized = $this->optimize_image_file($entry['tmp_name'], $mime, $destination, $thumbDestination);
                if (!is_array($optimized)) throw new RuntimeException('Gambar belum dapat dioptimasi. Gunakan JPG, PNG, atau WEBP yang valid.');
                @chmod($destination, 0640); @chmod($thumbDestination, 0640); $paths[] = $destination; $paths[] = $thumbDestination;
                $out[] = array('id' => function_exists('random_int') ? random_int(100000000, 999999999) : mt_rand(100000000, 999999999),
                    'original_name' => substr($entry['name'], 0, 180), 'stored_name' => $name, 'storage_path' => $destination,
                    'thumbnail_name' => $thumbName, 'thumbnail_path' => $thumbDestination, 'mime_type' => 'image/webp',
                    'file_size' => (int) @filesize($destination), 'thumbnail_file_size' => (int) @filesize($thumbDestination),
                    'width' => (int) $optimized['width'], 'height' => (int) $optimized['height']);
            }
        } catch (Throwable $exception) {
            finfo_close($finfo); $this->cleanup_paths($paths);
            $message = $exception instanceof RuntimeException
                ? $exception->getMessage() : 'Gambar belum dapat dioptimasi. Coba lagi dengan foto lain.';
            return array('files' => array(), 'paths' => array(), 'error' => $message);
        }
        finfo_close($finfo); return array('files' => $out, 'paths' => $paths, 'error' => NULL);
    }

    /** Re-encode an uploaded image as metadata-free WebP and create its card thumbnail. */
    private function optimize_image_file($source, $mime, $destination, $thumbDestination)
    {
        $loaders = array('image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp');
        if (!isset($loaders[$mime]) || !function_exists($loaders[$mime])) return NULL;
        $image = @call_user_func($loaders[$mime], $source);
        if (!$image) return NULL;
        $main = NULL; $thumb = NULL;
        try {
            if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($image);
            if ($mime === 'image/jpeg') {
                $oriented = $this->apply_jpeg_orientation($image, $source);
                if (!$oriented) return NULL;
                if ($oriented !== $image) @imagedestroy($image);
                $image = $oriented;
            }
            $main = $this->resize_image_canvas($image, 1600);
            $thumb = $this->resize_image_canvas($image, 640);
            if (!$this->write_webp_atomic($main, $destination, 84) || !$this->write_webp_atomic($thumb, $thumbDestination, 82)) {
                @unlink($destination); @unlink($thumbDestination); return NULL;
            }
            return array('width' => (int) @imagesx($main), 'height' => (int) @imagesy($main));
        } finally {
            if (is_object($main) || is_resource($main)) @imagedestroy($main);
            if (is_object($thumb) || is_resource($thumb)) @imagedestroy($thumb);
            if (is_object($image) || is_resource($image)) @imagedestroy($image);
        }
    }

    private function apply_jpeg_orientation($image, $source)
    {
        $orientation = 1;
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            $orientation = (int) ($exif['Orientation'] ?? 1);
        }
        switch ($orientation) {
            case 2: @imageflip($image, IMG_FLIP_HORIZONTAL); break;
            case 3: $image = @imagerotate($image, 180, 0); break;
            case 4: @imageflip($image, IMG_FLIP_VERTICAL); break;
            case 5: @imageflip($image, IMG_FLIP_HORIZONTAL); $image = @imagerotate($image, -90, 0); break;
            case 6: $image = @imagerotate($image, -90, 0); break;
            case 7: @imageflip($image, IMG_FLIP_HORIZONTAL); $image = @imagerotate($image, 90, 0); break;
            case 8: $image = @imagerotate($image, 90, 0); break;
        }
        return $image ?: NULL;
    }

    private function resize_image_canvas($image, $maxDimension)
    {
        $width = max(1, (int) @imagesx($image)); $height = max(1, (int) @imagesy($image));
        $scale = min(1, (float) $maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale)); $newHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, FALSE); imagesavealpha($canvas, TRUE);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagealphablending($canvas, TRUE); imagesavealpha($canvas, TRUE);
        return $canvas;
    }

    private function write_webp_atomic($image, $destination, $quality)
    {
        $temporary = $destination . '.tmp-' . bin2hex(random_bytes(6));
        if (!@imagewebp($image, $temporary, (int) $quality) || !is_file($temporary) || (int) @filesize($temporary) < 1) { @unlink($temporary); return FALSE; }
        @chmod($temporary, 0640);
        if (!@rename($temporary, $destination)) { @unlink($temporary); return FALSE; }
        return TRUE;
    }

    private function create_thumbnail_file($source, $mime, $destination)
    {
        $loaders = array('image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp');
        if (!isset($loaders[$mime]) || !function_exists($loaders[$mime])) return FALSE;
        $image = @call_user_func($loaders[$mime], $source); if (!$image) return FALSE;
        $thumb = NULL;
        try {
            if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($image);
            if ($mime === 'image/jpeg') {
                $oriented = $this->apply_jpeg_orientation($image, $source);
                if (!$oriented) return FALSE;
                if ($oriented !== $image) @imagedestroy($image);
                $image = $oriented;
            }
            if (!$image) return FALSE;
            $thumb = $this->resize_image_canvas($image, 640);
            return $this->write_webp_atomic($thumb, $destination, 82);
        } finally {
            if (is_object($thumb) || is_resource($thumb)) @imagedestroy($thumb);
            if (is_object($image) || is_resource($image)) @imagedestroy($image);
        }
    }

    private function image_paths(array $image)
    {
        $paths = array();
        $full = trim((string) ($image['storage_path'] ?? ''));
        if ($full !== '') $paths[] = $full;
        $thumb = trim((string) ($image['thumbnail_path'] ?? ''));
        if ($thumb === '' && $full !== '') $thumb = $this->image_variant_path($image, 'thumb');
        if ($thumb !== '' && !in_array($thumb, $paths, TRUE)) $paths[] = $thumb;
        return $paths;
    }

    private function private_storage_path()
    {
        $configured = trim((string) getenv('PRIVATE_STORAGE_PATH'));
        if (ENVIRONMENT === 'production' && $configured === '') return NULL;
        $path = $configured !== '' ? $configured : FCPATH . 'storage';
        if (!is_dir($path) && !@mkdir($path, 0750, TRUE) && !is_dir($path)) return NULL;
        $real = realpath($path); if ($real === FALSE || !is_dir($real) || !is_readable($real) || !is_writable($real)) return NULL;
        if (ENVIRONMENT === 'production') { $public = realpath(FCPATH); if ($public !== FALSE && strpos(rtrim($real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, rtrim($public, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) return NULL; }
        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function cleanup_paths(array $paths) { foreach ($paths as $path) if (is_string($path) && is_file($path)) @unlink($path); }

    private function cleanup_product_paths(array $paths, $productId)
    {
        $root = $this->private_storage_path();
        if ($root === NULL) return;
        $marketRoot = realpath($root . DIRECTORY_SEPARATOR . 'marketplace');
        if ($marketRoot === FALSE || !is_dir($marketRoot)) return;
        $prefix = rtrim($marketRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $namePrefix = (string) $productId . '-';
        foreach ($paths as $path) {
            if (!is_string($path) || $path === '' || is_link($path)) continue;
            $real = realpath($path);
            if ($real === FALSE || !is_file($real) || strpos($real, $prefix) !== 0 || strpos(basename($real), $namePrefix) !== 0) continue;
            @unlink($real);
        }
    }

    private function demo_categories()
    {
        return array(array('id' => 1, 'slug' => 'makanan-minuman', 'name' => 'Makanan & Minuman', 'sort_order' => 10), array('id' => 2, 'slug' => 'hasil-tani', 'name' => 'Hasil Tani', 'sort_order' => 20), array('id' => 3, 'slug' => 'kerajinan', 'name' => 'Kerajinan', 'sort_order' => 30), array('id' => 4, 'slug' => 'jasa', 'name' => 'Jasa', 'sort_order' => 40), array('id' => 5, 'slug' => 'lainnya', 'name' => 'Lainnya', 'sort_order' => 90));
    }

    private function demo_state()
    {
        $state = $this->session->userdata('warga_marketplace');
        if (!is_array($state)) $state = array();
        if (!isset($state['stores']) || !is_array($state['stores'])) $state['stores'] = array();
        if (!isset($state['products']) || !is_array($state['products'])) $state['products'] = $this->demo_products_seed();
        if (!isset($state['reviews']) || !is_array($state['reviews'])) $state['reviews'] = array();
        return $state;
    }

    private function save_demo_state(array $state) { $this->session->set_userdata('warga_marketplace', $state); }

    private function demo_products()
    {
        return $this->demo_state()['products'];
    }

    private function demo_products_seed()
    {
        $village = '00000000-0000-4000-8000-000000000001';
        return array(
            array('id' => 'demo-market-1', 'village_id' => $village, 'village_name' => 'Kampung Araboda', 'store_id' => 'demo-store-1', 'seller_user_id' => 1, 'category_id' => 2, 'category_slug' => 'hasil-tani', 'category_name' => 'Hasil Tani', 'name' => 'Ubi Jalar Pegunungan', 'description' => 'Hasil kebun segar dari warga kampung.', 'price' => '35000.00', 'stock' => 12, 'status' => 'published', 'store_name' => 'Kios Warga Araboda', 'store_whatsapp' => '081234567890', 'store_phone' => '081234567890', 'images' => array()),
            array('id' => 'demo-market-2', 'village_id' => $village, 'village_name' => 'Kampung Araboda', 'store_id' => 'demo-store-2', 'seller_user_id' => 2, 'category_id' => 3, 'category_slug' => 'kerajinan', 'category_name' => 'Kerajinan', 'name' => 'Noken Rajut Wamena', 'description' => 'Kerajinan tangan buatan warga.', 'price' => '175000.00', 'stock' => 5, 'status' => 'published', 'store_name' => 'Bumdes Araboda', 'store_whatsapp' => '081234567891', 'store_phone' => '081234567891', 'images' => array()),
            array('id' => 'demo-market-3', 'village_id' => $village, 'village_name' => 'Kampung Araboda', 'store_id' => 'demo-store-3', 'seller_user_id' => 3, 'category_id' => 1, 'category_slug' => 'makanan-minuman', 'category_name' => 'Makanan & Minuman', 'name' => 'Kopi Araboda', 'description' => 'Kopi pilihan dari pegunungan Jayawijaya.', 'price' => '85000.00', 'stock' => 20, 'status' => 'published', 'store_name' => 'Koperasi Kampung', 'store_whatsapp' => '081234567892', 'store_phone' => '081234567892', 'images' => array())
        );
    }
}
