<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — personal notification inbox.
 *
 * Every staff account can read (and clear) its own notifications; the rows are
 * always scoped to the signed-in user id, so no permission is required and no
 * account can read somebody else's inbox.
 */
class Notifications extends Admin_Controller
{
    /** Personal inbox — every staff account may read its own notifications. */
    protected $required_permission = null;

    public function index()
    {
        $this->page_title = 'Notifications';
        $rows = $this->db->where('userId', $this->vp_auth->id())
                         ->order_by('createdAt', 'DESC')
                         ->limit(100)
                         ->get('notifications')->result_array();
        $this->render('admin/notifications/index', ['rows' => $rows]);
    }

    public function read($id = null)
    {
        if (!$id) show_404();
        $this->db->update('notifications', ['read' => 1], ['id' => $id, 'userId' => $this->vp_auth->id()]);
        redirect('admin/notifications');
    }

    public function read_all()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->db->update('notifications', ['read' => 1], ['userId' => $this->vp_auth->id(), 'read' => 0]);
        $this->flash('success', 'All notifications marked as read.');
        redirect('admin/notifications');
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        if (!$id) show_404();
        $this->db->delete('notifications', ['id' => $id, 'userId' => $this->vp_auth->id()]);
        redirect('admin/notifications');
    }

    public function clear()
    {
        if ($this->input->method() !== 'post') show_404();
        $this->db->delete('notifications', ['userId' => $this->vp_auth->id(), 'read' => 1]);
        $this->flash('success', 'Read notifications cleared.');
        redirect('admin/notifications');
    }
}
