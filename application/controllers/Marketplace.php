<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pasar Digital warga. Semua record dibatasi pada village_id milik sesi aktif.
 */
class Marketplace extends App_Controller
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
        $listing = $this->marketplace->products($this->currentUser, $filters);
        $this->render('marketplace/index', array(
            'pageTitle' => 'Pasar Digital',
            'products' => $listing['items'],
            'listing' => $listing,
            'categories' => $this->marketplace->categories($this->currentUser),
            'store' => $this->marketplace->store_for_user($this->currentUser),
            'canManage' => $this->marketplace->can_manage($this->currentUser),
            'marketplaceReady' => $this->marketplace->is_ready()
        ));
    }

    public function show($id)
    {
        $product = $this->marketplace->product($id, $this->currentUser);
        if (!$product) show_404();
        $related = $this->marketplace->products($this->currentUser, array('category_id' => $product['category_id'], 'per_page' => 4));
        $this->render('marketplace/product', array(
            'pageTitle' => (string) $product['name'],
            'product' => $product,
            'related' => array_values(array_filter($related['items'], function ($item) use ($product) { return (string) $item['id'] !== (string) $product['id']; })),
            'canManage' => $this->marketplace->can_manage($this->currentUser),
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar')
        ));
    }

    public function create()
    {
        $this->require_manager();
        $this->render('marketplace/create', array(
            'pageTitle' => 'Tambah Produk',
            'categories' => $this->marketplace->categories($this->currentUser),
            'store' => $this->marketplace->store_for_user($this->currentUser),
            'product' => NULL,
            'editMode' => FALSE,
            'showBackButton' => TRUE,
            'backUrl' => site_url('pasar')
        ));
    }

    public function edit($id)
    {
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
            'backUrl' => site_url('pasar/produk/' . rawurlencode((string) $id))
        ));
    }

    public function store()
    {
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
        $this->require_post();
        $this->require_manager();
        $ok = $this->marketplace->archive_product($id, $this->currentUser);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Produk diarsipkan.' : 'Produk tidak ditemukan.');
        redirect('pasar');
    }

    public function store_settings()
    {
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
            'backUrl' => site_url('pasar')
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
                redirect('pasar');
            }
            $data['fieldErrors']['form'] = $result['message'] ?? 'Identitas toko belum dapat disimpan.';
        }
        $this->render('marketplace/store', $data);
    }

    public function image($id)
    {
        $image = $this->marketplace->image_for_user($id, $this->currentUser);
        if (!$image || empty($image['storage_path']) || !$this->stream_private_file($image['storage_path'], $image['original_name'] ?? '', 'inline')) show_404();
    }

    private function require_manager()
    {
        if (!$this->marketplace->can_manage($this->currentUser)) show_error('Akses mengelola Pasar Digital ditolak.', 403);
    }
}
