<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halyk Petroleum — navigation / menu management.
 *
 * The public header, footer columns and legal links are all rendered from the
 * `menu_items` table, so administrators fully control the site navigation.
 * The logo always links to the homepage and is deliberately not editable as a
 * menu item.
 */
class Menus extends Admin_Controller
{
    protected $required_permission = 'menus.manage';

    /** Menu locations rendered by the public theme. */
    private $locations = [
        'header'           => 'Header navigation',
        'footer_solutions' => 'Footer — column 1 (Solutions)',
        'footer_company'   => 'Footer — column 2 (Company)',
        'footer_legal'     => 'Footer — legal links',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Menu_item_model', 'Page_model']);
        $this->load->helper(['form', 'url', 'security_helper', 'cms_schema_helper']);
        vp_ensure_cms_tables();
    }

    public function index($menu = 'header')
    {
        $menu = array_key_exists($menu, $this->locations) ? $menu : 'header';
        $this->page_title = 'Navigation';
        $this->render('admin/menus/index', [
            'menu'      => $menu,
            'locations' => $this->locations,
            'items'     => $this->Menu_item_model->for_menu($menu, false),
            'pages'     => $this->Page_model->find_all([], ['title' => 'ASC']),
        ]);
    }

    public function save()
    {
        if ($this->input->method() !== 'post') show_404();

        $id   = $this->input->post('id');
        $menu = (string) $this->input->post('menu');
        if (!array_key_exists($menu, $this->locations)) {
            $this->flash('error', 'Unknown menu location.');
            return redirect('admin/menus');
        }

        $label = trim((string) $this->input->post('label'));
        $type  = in_array($this->input->post('type'), ['INTERNAL', 'PAGE', 'EXTERNAL'], true) ? $this->input->post('type') : 'INTERNAL';
        if ($label === '') {
            $this->flash('error', 'A menu label is required.');
            return redirect('admin/menus/index/' . $menu);
        }

        $data = [
            'menu'     => $menu,
            'label'    => $label,
            'type'     => $type,
            'url'      => $type === 'PAGE' ? null : (trim((string) $this->input->post('url')) ?: '/'),
            'pageId'   => $type === 'PAGE' ? ($this->input->post('pageId') ?: null) : null,
            'target'   => $this->input->post('target') === '_blank' ? '_blank' : '_self',
            'icon'     => trim((string) $this->input->post('icon')) ?: null,
            'isActive' => $this->input->post('isActive') ? 1 : 0,
            'updatedAt'=> date('Y-m-d H:i:s'),
        ];

        if ($id && ($existing = $this->Menu_item_model->find($id))) {
            $this->db->update('menu_items', $data, ['id' => $id]);
            $this->audit->log(AUDIT_UPDATE, 'menu_item', $id, ['label' => $label, 'menu' => $menu]);
            $this->flash('success', 'Menu item updated.');
        } else {
            $id = MY_Model::uuid();
            $data['id']        = $id;
            $data['sortOrder'] = $this->Menu_item_model->next_order($menu);
            $data['createdAt'] = date('Y-m-d H:i:s');
            $this->db->insert('menu_items', $data);
            $this->audit->log(AUDIT_CREATE, 'menu_item', $id, ['label' => $label, 'menu' => $menu]);
            $this->flash('success', 'Menu item added.');
        }

        redirect('admin/menus/index/' . $menu);
    }

    public function toggle($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Menu_item_model->find($id) : null;
        if (!$row) show_404();
        $new = empty($row['isActive']) ? 1 : 0;
        $this->db->update('menu_items', ['isActive' => $new, 'updatedAt' => date('Y-m-d H:i:s')], ['id' => $id]);
        $this->audit->log(AUDIT_UPDATE, 'menu_item', $id, ['isActive' => $new]);
        redirect('admin/menus/index/' . $row['menu']);
    }

    public function move($id = null, $dir = 'up')
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Menu_item_model->find($id) : null;
        if (!$row) show_404();

        $items = $this->Menu_item_model->for_menu($row['menu'], false);
        $ids   = array_column($items, 'id');
        $pos   = array_search($id, $ids, true);
        $swap  = $dir === 'down' ? $pos + 1 : $pos - 1;
        if ($pos !== false && isset($items[$swap])) {
            $this->db->update('menu_items', ['sortOrder' => (int) $items[$swap]['sortOrder']], ['id' => $items[$pos]['id']]);
            $this->db->update('menu_items', ['sortOrder' => (int) $items[$pos]['sortOrder']], ['id' => $items[$swap]['id']]);
            $this->audit->log(AUDIT_UPDATE, 'menu_item', $id, ['reorder' => $dir]);
        }
        redirect('admin/menus/index/' . $row['menu']);
    }

    public function delete($id = null)
    {
        if ($this->input->method() !== 'post') show_404();
        $row = $id ? $this->Menu_item_model->find($id) : null;
        if (!$row) show_404();
        $this->Menu_item_model->delete($id);
        $this->audit->log(AUDIT_DELETE, 'menu_item', $id, ['label' => $row['label'], 'menu' => $row['menu']]);
        $this->flash('success', 'Menu item deleted.');
        redirect('admin/menus/index/' . $row['menu']);
    }
}
