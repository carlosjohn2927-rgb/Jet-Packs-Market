<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - public Products controller.
 */
class Products extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Product_model', 'Category_model', 'Industry_model', 'Product_image_model', 'Specification_model', 'Product_download_model', 'Related_product_model']);
    }

    public function index()
    {
        $this->page_title = 'Products';
        $this->page_description = 'Browse the Vortex Precision product catalog: valves, pumps, heat exchangers, pressure vessels, filtration and instrumentation.';

        $category = $this->input->get('category');
        $industry = $this->input->get('industry');
        $search   = $this->input->get('q');
        $page     = max(1, (int) $this->input->get('page'));
        $per      = 12;

        $where = ['isActive' => 1];
        if ($category) {
            $cat = $this->Category_model->find_one(['slug' => $category, 'isActive' => 1]);
            if ($cat) $where['categoryId'] = $cat['id'];
        }
        if ($industry) {
            $ind = $this->Industry_model->find_one(['slug' => $industry, 'isActive' => 1]);
            if ($ind) {
                $where['industryIds LIKE'] = '%' . $ind['id'] . '%';
            }
        }

        $search_fields = ['name','shortDescription','description','sku','material','pressure','temperature'];
        $result = $this->Product_model->paginate($where, $per, $page, ['featured' => 'DESC', 'createdAt' => 'DESC'], $search, $search_fields);

        // Attach the primary image + category slug so every catalog card can
        // show a real photo instead of the generic placeholder.
        $result['rows'] = $this->Product_model->attach_images($result['rows']);

        $data = [
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'total_pages' => $result['total_pages'],
            'page'        => $result['page'],
            'categories'  => $this->Category_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], 12),
            'industries'  => $this->Industry_model->find_all(['isActive' => 1], ['sortOrder' => 'ASC'], 12),
            'current_category' => $category,
            'current_industry' => $industry,
            'search'      => $search,
            'base_url'    => base_url('products') . '?' . http_build_query(array_filter(['category' => $category, 'industry' => $industry, 'q' => $search])) . '&page={page}',
        ];
        $this->render('products/index', $data);
    }

    public function view($slug = null)
    {
        if (!$slug) show_404();
        $product = $this->Product_model->find_by_slug($slug);
        if (!$product) show_404();

        // Bump view count
        $this->db->set('views', 'views+1', false)
                 ->where('id', $product['id'])
                 ->update('products');

        $images    = $this->Product_image_model->find_all(['productId' => $product['id']], ['sortOrder' => 'ASC', 'isPrimary' => 'DESC']);
        $specs     = $this->Specification_model->find_all(['productId' => $product['id']], ['sortOrder' => 'ASC']);
        $downloads = $this->Product_download_model->find_all(['productId' => $product['id']], ['createdAt' => 'DESC']);
        $related   = $this->Product_model->attach_images(
            $this->Related_product_model->get_related($product['id'], 4)
        );

        $category  = $product['categoryId'] ? $this->Category_model->find($product['categoryId']) : null;
        if ($category) {
            $product['categorySlug'] = $category['slug'];
        }

        $this->page_title = $product['metaTitle'] ?: $product['name'];
        $this->page_description = $product['metaDescription'] ?: vp_truncate(strip_tags($product['shortDescription'] ?? $product['description']), 160);

        $this->render('products/view', [
            'product'   => $product,
            'images'    => $images,
            'specs'     => $specs,
            'downloads' => $downloads,
            'related'   => $related,
            'category'  => $category,
            'industries'=> $this->_industries_for($product['industryIds']),
            'certifications' => $product['certifications'] ? json_decode($product['certifications'], true) : [],
        ]);
    }

    private function _industries_for($json)
    {
        $ids = is_string($json) ? json_decode($json, true) : (array) $json;
        if (empty($ids)) return [];
        $out = [];
        foreach ($ids as $iid) {
            $r = $this->Industry_model->find($iid);
            if ($r) $out[] = $r;
        }
        return $out;
    }
}
