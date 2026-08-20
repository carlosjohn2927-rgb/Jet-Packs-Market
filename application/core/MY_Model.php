<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vortex Precision - base model.
 * Provides:
 *  - find / find_one / save / update / delete
 *  - paginate helper
 *  - automatic UUID generation
 *  - timestamps (created_at, updated_at)
 *  - soft delete if the table has deleted_at
 */
class MY_Model extends CI_Model
{
    /** @var string Table name (override in child). */
    protected $table = '';

    /** @var string Primary key column. */
    protected $primary_key = 'id';

    /** @var array Columns that may be filled from $data. */
    protected $fillable = [];

    /** @var bool Use created_at / updated_at columns. */
    protected $timestamps = true;

    /** @var bool Use soft delete (deleted_at column). */
    protected $soft_delete = false;

    /** @var string Soft delete column. */
    protected $deleted_at_field = 'deleted_at';

    /** @var string Order by default, e.g. ['col' => 'ASC'] */
    protected $order_by = ['id' => 'DESC'];

    public function __construct()
    {
        parent::__construct();
    }

    /** Generate a UUID v4 string. */
    public static function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /** Get the table name. */
    public function table()
    {
        return $this->table;
    }

    /**
     * Find a single row by primary key.
     */
    public function find($id)
    {
        return $this->db->get_where($this->table, [$this->primary_key => $id])->row_array();
    }

    /**
     * Get one row matching $where.
     */
    public function find_one(array $where)
    {
        return $this->db->get_where($this->table, $where, 1)->row_array();
    }

    /**
     * Get all rows matching $where.
     */
    public function find_all(array $where = [], $order_by = null, $limit = null, $offset = 0)
    {
        if ($this->soft_delete && $this->db->table_exists($this->table)
            && !$this->db->field_exists($this->deleted_at_field, $this->table)) {
            $this->soft_delete = false;
        }
        if ($this->soft_delete) {
            $this->db->where($this->deleted_at_field . ' IS NULL', null, false);
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        if ($order_by) {
            foreach ($order_by as $col => $dir) {
                $this->db->order_by($this->db->protect_identifiers($col), $dir);
            }
        } elseif ($this->order_by) {
            foreach ($this->order_by as $col => $dir) {
                $this->db->order_by($this->db->protect_identifiers($col), $dir);
            }
        }
        if ($limit) {
            $this->db->limit($limit, $offset);
        }
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Count rows matching $where.
     */
    public function count(array $where = [])
    {
        if ($this->soft_delete) {
            $this->db->where($this->deleted_at_field . ' IS NULL', null, false);
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        return $this->db->count_all_results($this->table);
    }

    /**
     * Insert a new row. Auto-fills uuid + timestamps.
     *
     * @return string The new ID.
     */
    public function insert(array $data)
    {
        $data = $this->_filter_fillable($data);
        if (!isset($data[$this->primary_key])) {
            $data[$this->primary_key] = self::uuid();
        }
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!$this->db->field_exists('created_at', $this->table) || !isset($data['created_at'])) {
                // We'll let MySQL DEFAULT do its job unless caller overrode
            }
        }
        $this->db->insert($this->table, $data);
        return $data[$this->primary_key];
    }

    /**
     * Update a row by id.
     *
     * @return int Number of affected rows.
     */
    public function update($id, array $data)
    {
        $data = $this->_filter_fillable($data);
        return $this->db->update($this->table, $data, [$this->primary_key => $id]);
    }

    /**
     * Update rows matching $where.
     */
    public function update_where(array $where, array $data)
    {
        $data = $this->_filter_fillable($data);
        return $this->db->update($this->table, $data, $where);
    }

    /**
     * Delete a row by id (or soft delete if enabled).
     */
    public function delete($id)
    {
        if ($this->soft_delete) {
            return $this->db->update($this->table, [$this->deleted_at_field => date('Y-m-d H:i:s')], [$this->primary_key => $id]);
        }
        return $this->db->delete($this->table, [$this->primary_key => $id]);
    }

    /**
     * Hard delete a row.
     */
    public function hard_delete($id)
    {
        return $this->db->delete($this->table, [$this->primary_key => $id]);
    }

    /**
     * Run a paginated query and return [rows, total, total_pages].
     */
    public function paginate(array $where = [], $per_page = 12, $page = 1, $order_by = null, $search = null, $search_fields = [])
    {
        $page = max(1, (int) $page);

        if ($this->soft_delete) {
            $this->db->where($this->deleted_at_field . ' IS NULL', null, false);
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        if ($search && $search_fields) {
            $this->db->group_start();
            foreach ($search_fields as $i => $f) {
                $this->db->or_like($f, $search);
            }
            $this->db->group_end();
        }
        $total = $this->db->count_all_results($this->table);

        if ($this->soft_delete) {
            $this->db->where($this->deleted_at_field . ' IS NULL', null, false);
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        if ($search && $search_fields) {
            $this->db->group_start();
            foreach ($search_fields as $i => $f) {
                $this->db->or_like($f, $search);
            }
            $this->db->group_end();
        }
        if ($order_by) {
            foreach ($order_by as $col => $dir) {
                $this->db->order_by($this->db->protect_identifiers($col), $dir);
            }
        } elseif ($this->order_by) {
            foreach ($this->order_by as $col => $dir) {
                $this->db->order_by($this->db->protect_identifiers($col), $dir);
            }
        }
        $this->db->limit($per_page, ($page - 1) * $per_page);
        $rows = $this->db->get($this->table)->result_array();

        return [
            'rows'  => $rows,
            'total' => (int) $total,
            'per_page' => $per_page,
            'page'  => $page,
            'total_pages' => $per_page > 0 ? (int) ceil($total / $per_page) : 1,
        ];
    }

    /** Filter $data to $fillable columns. */
    private function _filter_fillable(array $data)
    {
        if (empty($this->fillable)) return $data;
        $out = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $this->fillable, true)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
