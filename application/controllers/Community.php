<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Community extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Community_model', 'community');
        $this->output->set_header('Cache-Control: no-store, private');
    }

    public function announcements()
    {
        // Announcements contain tenant-scoped information.  Do not expose a
        // public catalogue: the model query below always filters by the
        // authenticated user's village_id.
        $this->require_authentication();
        $this->output->set_header('X-Robots-Tag: noindex, nofollow, noarchive');
        $items = $this->community->announcements($this->currentUser);
        $this->render('community/announcements', array('pageTitle' => 'Pengumuman',
            'items' => $items,
            'canManage' => $this->community->can_manage($this->currentUser),
            'ready' => $this->community->ready()));
    }

    public function announcement($id)
    {
        // Detail pages use the same tenant guard as the catalogue.  This is
        // deliberately checked before looking up the record so an anonymous
        // visitor cannot probe announcement IDs across villages.
        $this->require_authentication();
        $this->output->set_header('X-Robots-Tag: noindex, nofollow, noarchive');
        $item = $this->community->announcement($id, $this->currentUser);
        if (!$item) show_404();
        // Opening an announcement directly from the announcement catalogue
        // must consume the same notification that would be consumed through
        // /notifikasi/buka/{id}.  The target is tenant- and user-scoped by
        // Notification_model, so public visitors and unrelated notifications
        // remain untouched.
        $this->load->model('Notification_model');
        $this->Notification_model->mark_target_read($this->currentUser['id'], 'pengumuman/' . (string) $id);
        $this->render('community/announcement', array('pageTitle' => 'Pengumuman', 'item' => $item,
            'canManage' => $this->community->can_manage($this->currentUser), 'showBackButton' => true, 'backUrl' => site_url('pengumuman')));
    }

    public function publish()
    {
        $this->require_authentication();
        $this->require_post();
        if (!$this->community->can_manage($this->currentUser)) show_error('Akses ditolak.', 403);
        $this->form_validation->set_rules('title', 'Judul', 'trim|required|max_length[180]');
        $this->form_validation->set_rules('body', 'Isi pengumuman', 'trim|required|min_length[10]|max_length[10000]');
        if (!$this->form_validation->run()) $this->redirect_with('pengumuman', 'error', trim(strip_tags(validation_errors())));
        $id = $this->community->publish($this->currentUser, trim((string)$this->input->post('title')), trim((string)$this->input->post('body')));
        $this->redirect_with($id ? 'pengumuman/'.$id : 'pengumuman', $id ? 'success' : 'error', $id ? 'Pengumuman diterbitkan.' : 'Pengumuman belum dapat disimpan.');
    }

    public function delete($id)
    {
        $this->require_authentication();
        $this->require_post();
        if (!$this->community->can_manage($this->currentUser)) show_error('Akses ditolak.', 403);
        $ok = $this->community->delete_announcement($id, $this->currentUser);
        $this->redirect_with('pengumuman', $ok ? 'success' : 'error', $ok ? 'Pengumuman dihapus.' : 'Pengumuman tidak ditemukan.');
    }

    public function complaints()
    {
        $this->require_authentication();
        $this->render('community/complaints', array('pageTitle' => 'Pengaduan', 'items' => $this->community->complaints($this->currentUser),
            'canManage' => $this->community->can_manage($this->currentUser), 'ready' => $this->community->ready()));
    }

    public function submit()
    {
        if (!$this->currentUser && $this->wants_json()) {
            return $this->json(array('success' => FALSE, 'message' => 'Sesi login berakhir. Silakan masuk kembali.', 'login_url' => site_url('login')), 401);
        }
        $this->require_authentication();
        $this->require_post();
        if (($this->currentUser['role_slug'] ?? '') !== 'warga') {
            if ($this->wants_json()) return $this->json(array('success' => FALSE, 'message' => 'Akun ini tidak memiliki akses untuk membuat pengaduan.'), 403);
            show_error('Akses ditolak.', 403);
        }
        $this->form_validation->set_rules('title', 'Judul', 'trim|required|max_length[180]');
        $this->form_validation->set_rules('body', 'Isi pengaduan', 'trim|required|min_length[10]|max_length[5000]');
        $this->form_validation->set_rules('location', 'Lokasi', 'trim|max_length[255]');
        if (!$this->form_validation->run()) {
            $message = trim(strip_tags(validation_errors())) ?: 'Periksa kembali data pengaduan.';
            if ($this->wants_json()) return $this->json(array('success' => FALSE, 'message' => $message), 422);
            $this->redirect_with('pengaduan', 'error', $message);
        }
        $id = $this->community->submit_complaint($this->currentUser, trim((string)$this->input->post('title')),
            trim((string)$this->input->post('body')), trim((string)$this->input->post('location')));
        $message = $id
            ? 'Pengaduan dikirim kepada Kepala ' . $this->institution_label() . ' dan Sekretaris ' . $this->institution_label() . '.'
            : 'Pengaduan belum dapat dikirim. Pastikan akun terverifikasi dan batas 10 pengaduan per hari belum tercapai.';
        if ($this->wants_json()) {
            if (!$id) return $this->json(array('success' => FALSE, 'message' => $message), 422);
            $item = $this->community->complaint($id, $this->currentUser);
            return $this->json(array(
                'success' => TRUE,
                'message' => $message,
                'id' => $id,
                'url' => site_url('pengaduan/'.$id),
                'item_html' => $this->load->view('community/complaint_item', array('item' => $item, 'canManage' => FALSE), TRUE)
            ));
        }
        $this->redirect_with($id ? 'pengaduan/'.$id : 'pengaduan', $id ? 'success' : 'error', $message);
    }

    public function complaint($id)
    {
        $this->require_authentication();
        $item = $this->community->complaint($id, $this->currentUser);
        if (!$item) show_404();
        $this->render('community/complaint', array('pageTitle' => 'Detail Pengaduan', 'item' => $item,
            'replies' => $this->community->replies($id), 'canManage' => $this->community->can_manage($this->currentUser),
            'showBackButton' => true, 'backUrl' => site_url('pengaduan')));
    }

    public function reply($id)
    {
        if (!$this->currentUser && $this->wants_json()) {
            return $this->json(array('success' => FALSE, 'message' => 'Sesi login berakhir. Silakan masuk kembali.', 'login_url' => site_url('login')), 401);
        }
        $this->require_authentication();
        $this->require_post();
        if (!$this->community->can_manage($this->currentUser)) {
            if ($this->wants_json()) return $this->json(array('success' => FALSE, 'message' => 'Akun ini tidak memiliki akses untuk menanggapi pengaduan.'), 403);
            show_error('Akses ditolak.', 403);
        }
        $this->form_validation->set_rules('message', 'Tanggapan', 'trim|required|min_length[5]|max_length[3000]');
        if (!$this->form_validation->run()) {
            $message = trim(strip_tags(validation_errors())) ?: 'Periksa kembali isi tanggapan.';
            if ($this->wants_json()) return $this->json(array('success' => FALSE, 'message' => $message), 422);
            $this->redirect_with('pengaduan/'.$id, 'error', $message);
        }
        $ok = $this->community->reply($id, $this->currentUser, trim((string)$this->input->post('message')), $this->input->post('status'));
        $message = $ok ? 'Tanggapan berhasil dikirim.' : 'Tanggapan belum dapat disimpan.';
        if ($this->wants_json()) {
            if (!$ok) return $this->json(array('success' => FALSE, 'message' => $message), 422);
            $item = $this->community->complaint($id, $this->currentUser);
            $replies = $this->community->replies($id);
            return $this->json(array(
                'success' => TRUE,
                'message' => $message,
                'status' => $item['status'],
                'status_label' => warga_complaint_status($item['status']),
                'reply_count' => count($replies),
                'replies_html' => $this->load->view('community/reply_items', array('replies' => $replies), TRUE)
            ));
        }
        $this->redirect_with('pengaduan/'.$id, $ok ? 'success' : 'error', $message);
    }

    public function contact()
    {
        $this->require_authentication();
        $village = $this->community->village($this->currentUser['village_id'], $this->currentUser['village_name'] ?? '');
        $this->render('community/contact', array('pageTitle' => 'Kontak '.$village['institution'], 'village' => $village));
    }

    public function privacy()
    {
        // Legal documents must be readable by anyone (including Play Store
        // reviewers) without creating an account.  Use the signed-in tenant
        // when available, otherwise render only the configured public
        // identity and never query resident-specific data.
        $village = $this->legal_village_context();
        $this->render('community/privacy', array(
            'pageTitle' => 'Kebijakan Privasi',
            'village' => $village,
            'supportEmail' => $this->public_support_email(),
            'showBackButton' => TRUE,
            'backUrl' => $this->currentUser ? site_url('akun') : site_url('dashboard')
        ));
    }

    public function terms()
    {
        // Keep the terms page public for store listing and policy links.
        $village = $this->legal_village_context();
        $this->render('community/terms', array(
            'pageTitle' => 'Syarat & Ketentuan',
            'village' => $village,
            'supportEmail' => $this->public_support_email(),
            'showBackButton' => TRUE,
            'backUrl' => $this->currentUser ? site_url('akun') : site_url('dashboard')
        ));
    }

    public function account_deletion()
    {
        // Google Play links directly to this page, so it must remain available
        // without a resident session. Requests go to the developer's dedicated
        // support address and are verified before any account data is changed.
        $supportEmail = $this->public_support_email();

        $developerName = trim((string) (getenv('PUBLIC_DEVELOPER_NAME') ?: 'PT. MediaVerse Inovasi Nusantara'));
        if ($developerName === '') $developerName = 'PT. MediaVerse Inovasi Nusantara';

        $this->render('community/account_deletion', array(
            'pageTitle' => 'Permintaan Penghapusan Akun',
            'village' => $this->legal_village_context(),
            'supportEmail' => $supportEmail,
            'developerName' => $developerName,
            'showBackButton' => TRUE,
            'backUrl' => $this->currentUser ? site_url('akun') : site_url('dashboard')
        ));
    }

    /**
     * Return only the identity needed by public legal documents.  A visitor
     * must not need a session (or receive a resident's tenant record) just to
     * read the privacy policy and terms of service.
     */
    private function legal_village_context()
    {
        if ($this->currentUser) {
            return $this->community->village(
                $this->currentUser['village_id'] ?? '',
                $this->currentUser['village_name'] ?? ''
            );
        }

        return array(
            'name' => trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya',
            'institution' => $this->institution_label(),
            'contact' => array()
        );
    }

    private function public_support_email()
    {
        $email = trim((string) (getenv('PUBLIC_ACCOUNT_DELETION_EMAIL') ?: 'admin@mediaverse.co.id'));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'admin@mediaverse.co.id';
    }

    private function wants_json()
    {
        if ($this->input->is_ajax_request()) return TRUE;
        return stripos((string) $this->input->get_request_header('Accept', TRUE), 'application/json') !== FALSE;
    }
}
