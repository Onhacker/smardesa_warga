<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pasar Digital warga. Katalog terbit bersifat publik lintas kampung;
 * operasi pengelolaan tetap dibatasi pada sesi dan kepemilikan penjual.
 */
class Marketplace extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Marketplace_model', 'marketplace');
        $this->output->set_header('Cache-Control: no-store, private');
    }

    public function index()
    {
        $filters = array(
            'q' => $this->input->get('q', TRUE),
            'category_id' => $this->input->get('category_id', TRUE),
            'sort' => $this->input->get('sort', TRUE),
            'page' => $this->input->get('page', TRUE),
            'per_page' => 12
        );
        // Katalog publik dimuat AJAX-first seperti /ausi/produk: halaman HTML
        // hanya menyiapkan kerangka, lalu request pertama (maks. 12 item)
        // dilakukan oleh market.js. Ini mengurangi HTML awal dan mencegah
        // browser membangun seluruh katalog sebelum pengguna scroll.
        $requestedPage = max(1, (int) ($filters['page'] ?? 1));
        $listing = array(
            'items' => array(),
            'total' => 0,
            'page' => $requestedPage,
            'pages' => 1,
            'per_page' => 12,
            'filters' => array(
                'q' => trim((string) ($filters['q'] ?? '')),
                'category_id' => max(0, (int) ($filters['category_id'] ?? 0)),
                'sort' => trim((string) ($filters['sort'] ?? 'newest')) ?: 'newest'
            ),
            'ready' => $this->marketplace->is_ready()
        );
        $viewer = array();
        $this->render('marketplace/index', array(
            'pageTitle' => 'Pasar Digital',
            'products' => $listing['items'],
            'listing' => $listing,
            'categories' => $this->marketplace->categories($viewer),
            'store' => $this->currentUser ? $this->marketplace->store_for_user($this->currentUser) : NULL,
            'canManage' => $this->currentUser ? $this->marketplace->can_manage($this->currentUser) : FALSE,
            'marketplaceReady' => $this->marketplace->is_ready()
        ));
    }

    /**
     * Potongan katalog publik untuk filter dan infinite scroll. Respons hanya
     * berisi kartu produk agar browser tidak perlu membangun ulang layout.
     */
    public function list_ajax()
    {
        $filters = array(
            'q' => $this->input->get('q', TRUE),
            'category_id' => $this->input->get('category_id', TRUE),
            'sort' => $this->input->get('sort', TRUE),
            'page' => $this->input->get('page', TRUE),
            'per_page' => $this->input->get('per_page', TRUE) ?: 12,
            'public_all' => TRUE
        );
        $listing = $this->marketplace->products(array(), $filters);
        $itemsHtml = $this->load->view('marketplace/product_cards', array(
            'products' => $listing['items'],
            'eagerFirst' => FALSE
        ), TRUE);
        $emptyHtml = $this->load->view('marketplace/empty_state', array(
            'marketplaceReady' => $this->marketplace->is_ready(),
            'canManage' => $this->currentUser ? $this->marketplace->can_manage($this->currentUser) : FALSE
        ), TRUE);
        $page = max(1, (int) ($listing['page'] ?? 1));
        $pages = max(1, (int) ($listing['pages'] ?? 1));

        return $this->json(array(
            'success' => TRUE,
            'items_html' => $itemsHtml,
            'empty_html' => $emptyHtml,
            'count' => max(0, (int) ($listing['total'] ?? 0)),
            'page' => $page,
            'pages' => $pages,
            'per_page' => max(1, (int) ($listing['per_page'] ?? 12)),
            'has_more' => $page < $pages,
            'ready' => !isset($listing['ready']) || (bool) $listing['ready'],
            'filters' => isset($listing['filters']) && is_array($listing['filters']) ? $listing['filters'] : array()
        ));
    }

    public function show($id)
    {
        $viewer = is_array($this->currentUser) ? $this->currentUser : array();
        $product = $this->marketplace->product($id, $viewer, TRUE);
        if (!$product) show_404();
        $related = $this->marketplace->products($viewer, array('category_id' => $product['category_id'], 'per_page' => 4, 'public_all' => TRUE, 'skip_total' => TRUE));
        $this->render('marketplace/product', array(
            'pageTitle' => (string) $product['name'],
            'product' => $product,
            'related' => array_values(array_filter($related['items'], function ($item) use ($product) { return (string) $item['id'] !== (string) $product['id']; })),
            'canManage' => $this->currentUser ? $this->marketplace->can_manage($this->currentUser) : FALSE,
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar')
        ));
    }

    /**
     * Public storefront. Visitors may browse a seller's active identity and
     * published products without signing in; unpublished products are never
     * included in this view.
     */
    public function public_store($id)
    {
        $store = $this->marketplace->public_store($id);
        if (!$store) show_404();

        $storeId = (string) ($store['id'] ?? $id);
        $listing = $this->marketplace->products(array(), array(
            'public_all' => TRUE,
            'store_id' => $storeId,
            'sort' => 'newest',
            'per_page' => 48
        ));
        $this->render('marketplace/store_public', array(
            'pageTitle' => (string) ($store['name'] ?? 'Toko warga'),
            'store' => $store,
            'products' => $listing['items'],
            'listing' => $listing,
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar')
        ));
    }

    /**
     * Store a public product rating asynchronously. The catalogue and detail
     * pages remain readable without login; only submitting a review requires
     * an authenticated warga/staff account.
     */
    public function rating($id)
    {
        if (strtoupper($this->input->method(TRUE)) !== 'POST') {
            return $this->json(array('success' => FALSE, 'message' => 'Metode permintaan tidak diizinkan.'), 405);
        }
        if (!$this->currentUser || empty($this->currentUser['id'])) {
            $this->session->set_userdata('intended_url', site_url('pasar/produk/' . rawurlencode((string) $id)));
            return $this->json(array(
                'success' => FALSE,
                'message' => 'Login diperlukan. Silakan masuk terlebih dahulu untuk memberi rating.',
                'login_url' => site_url('login')
            ), 401);
        }
        $result = $this->marketplace->save_review(
            $this->currentUser,
            $id,
            $this->input->post('rating', TRUE),
            $this->input->post('comment', TRUE)
        );
        if (empty($result['success'])) return $this->json($result, 422);
        $review = isset($result['review']) && is_array($result['review']) ? $result['review'] : array();
        return $this->json(array(
            'success' => TRUE,
            'message' => 'Rating dan komentar berhasil disimpan.',
            'summary' => isset($result['summary']) ? $result['summary'] : array(),
            'review' => $review,
            'review_html' => $this->load->view('marketplace/review_item', array('review' => $review), TRUE)
        ));
    }

    public function create()
    {
        $this->require_authentication();
        $this->require_manager();
        $this->render('marketplace/create', array(
            'pageTitle' => 'Tambah Produk',
            'categories' => $this->marketplace->categories($this->currentUser),
            'store' => $this->marketplace->store_for_user($this->currentUser),
            'product' => NULL,
            'editMode' => FALSE,
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar/tokoku')
        ));
    }

    public function edit($id)
    {
        $this->require_authentication();
        $this->require_manager();
        $product = $this->marketplace->product_owned($id, $this->currentUser);
        if (!$product) show_404();
        $this->render('marketplace/create', array(
            'pageTitle' => 'Edit Produk',
            'categories' => $this->marketplace->categories($this->currentUser),
            'store' => $this->marketplace->store_for_user($this->currentUser),
            'product' => $product,
            'editMode' => TRUE,
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar/tokoku')
        ));
    }

    public function store()
    {
        $this->require_authentication();
        $this->require_post();
        $this->require_manager();
        $this->form_validation->set_rules('name', 'Nama produk', 'trim|required|max_length[180]');
        $this->form_validation->set_rules('category_id', 'Kategori', 'trim|required|integer');
        $this->form_validation->set_rules('price', 'Harga', 'trim|required|max_length[30]');
        $this->form_validation->set_rules('description', 'Deskripsi', 'trim|max_length[5000]');
        $this->form_validation->set_rules('stock', 'Stok', 'trim|integer');
        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', trim(strip_tags(validation_errors())) ?: 'Form produk belum lengkap.');
            redirect($this->input->post('product_id', TRUE) ? 'pasar/produk/' . rawurlencode((string) $this->input->post('product_id', TRUE)) . '/ubah' : 'pasar/buat');
        }
        $data = array(
            'id' => $this->input->post('product_id', TRUE),
            'category_id' => $this->input->post('category_id', TRUE),
            'name' => $this->input->post('name', TRUE),
            'description' => $this->input->post('description', TRUE),
            'price' => $this->input->post('price', TRUE),
            'stock' => $this->input->post('stock', TRUE),
            'status' => $this->input->post('status', TRUE) ?: 'published'
        );
        $result = $this->marketplace->save_product($this->currentUser, $data, isset($_FILES['product_images']) && is_array($_FILES['product_images']) ? $_FILES['product_images'] : array());
        if (empty($result['success'])) {
            $this->session->set_flashdata('error', $result['message'] ?? 'Produk belum dapat disimpan.');
            redirect($data['id'] ? 'pasar/produk/' . rawurlencode((string) $data['id']) . '/ubah' : 'pasar/buat');
        }
        $this->session->set_flashdata('success', 'Produk berhasil disimpan.');
        redirect('pasar/produk/' . rawurlencode((string) $result['id']));
    }

    public function archive($id)
    {
        $this->require_authentication();
        $this->require_post();
        $this->require_manager();
        $ok = $this->marketplace->archive_product($id, $this->currentUser);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Produk diarsipkan.' : 'Produk tidak ditemukan.');
        redirect('pasar/tokoku');
    }

    public function delete_product($id)
    {
        $this->require_authentication();
        $this->require_post();
        $this->require_manager();
        $ok = $this->marketplace->delete_product($id, $this->currentUser);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Produk berhasil dihapus.' : 'Produk tidak ditemukan atau tidak dapat dihapus.');
        redirect('pasar/tokoku');
    }

    public function store_settings()
    {
        $this->require_authentication();
        $this->require_manager();
        $store = $this->marketplace->store_for_user($this->currentUser);
        $data = array(
            'pageTitle' => 'Identitas Toko',
            'store' => $store,
            'formValues' => array(
                'name' => (string) ($store['name'] ?? ''),
                'description' => (string) ($store['description'] ?? ''),
                'whatsapp' => (string) ($store['whatsapp'] ?? ''),
                'phone' => (string) ($store['phone'] ?? ''),
                'address' => (string) ($store['address'] ?? '')
            ),
            'fieldErrors' => array(),
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar/tokoku')
        );
        if ($this->input->method(TRUE) === 'POST') {
            $data['formValues'] = array(
                'name' => trim((string) $this->input->post('name', TRUE)),
                'description' => trim((string) $this->input->post('description', TRUE)),
                'whatsapp' => trim((string) $this->input->post('whatsapp', TRUE)),
                'phone' => trim((string) $this->input->post('phone', TRUE)),
                'address' => trim((string) $this->input->post('address', TRUE))
            );
            $result = $this->marketplace->save_store($this->currentUser, $data['formValues']);
            if (!empty($result['success'])) {
                $this->session->set_flashdata('success', 'Identitas toko berhasil disimpan.');
                redirect('pasar/tokoku');
            }
            $data['fieldErrors']['form'] = $result['message'] ?? 'Identitas toko belum dapat disimpan.';
        }
        $this->render('marketplace/store', $data);
    }

    public function image($id)
    {
        $viewer = is_array($this->currentUser) ? $this->currentUser : array();
        $image = $this->marketplace->image_for_user($id, $viewer, TRUE);
        if (!$image || empty($image['storage_path'])) show_404();
        $variant = strtolower(trim((string) $this->input->get('variant', TRUE)));
        $path = $this->marketplace->image_variant_path($image, $variant === 'thumb' ? 'thumb' : 'full');
        $thumbnailReady = FALSE;
        if ($variant === 'thumb') {
            $thumbnail = $this->marketplace->ensure_thumbnail($image);
            if ($thumbnail !== '') { $path = $thumbnail; $thumbnailReady = TRUE; }
            if ($path === '' || !is_file($path)) $path = $this->marketplace->image_variant_path($image, 'full');
        }
        $isPublic = (string) ($image['status'] ?? 'published') === 'published';
        // Do not cache a full-image fallback under a thumbnail URL. Once GD
        // is available, the same versioned URL can then receive the real thumb.
        $cacheSeconds = $isPublic && ($variant !== 'thumb' || $thumbnailReady) ? 31536000 : 0;
        $cacheToken = $this->marketplace->image_cache_token($image);
        if ($path === '' || !$this->stream_private_file($path, $image['original_name'] ?? '', 'inline', '', $cacheSeconds, $cacheToken)) show_404();
    }

    /**
     * Seller workspace.  The public catalogue remains focused on discovery;
     * all product and store management lives under the clearly named Tokoku
     * page and is guarded by the same role policy as the existing forms.
     */
    public function tokoku()
    {
        $this->require_authentication();
        $this->require_manager();
        $listing = $this->marketplace->products($this->currentUser, array('only_own' => TRUE, 'per_page' => 48, 'public_all' => TRUE));
        $this->render('marketplace/tokoku', array(
            'pageTitle' => 'Tokoku',
            'store' => $this->marketplace->store_for_user($this->currentUser),
            'products' => $listing['items'],
            'listing' => $listing,
            'canManage' => TRUE,
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar')
        ));
    }

    private function require_manager()
    {
        if (!$this->currentUser || !$this->marketplace->can_manage($this->currentUser)) show_error('Akses mengelola Pasar Digital ditolak.', 403);
    }
}
