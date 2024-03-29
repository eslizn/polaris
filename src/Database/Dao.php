<?php

namespace Polaris\Database;

use Polaris\Database\Exception\NoRecordException;
use Psr\Container\ContainerInterface;

/**
 * Class Dao
 *
 * @package Database
 */
class Dao extends Connection
{

    /**
     * 左连接
     *
     * @var string
     */
    const LeftJoin = 'LEFT JOIN';

    /**
     * 右链接
     *
     * @var string
     */
    const RightJoin = 'RIGHT JOIN';

    /**
     * 全连接
     *
     * @var string
     */
    const InnerJoin = 'INNER JOIN';

    /**
     * 交叉连接
     *
     * @var string
     */
    const CrossJoin = 'CROSS JOIN';

    /**
     * 数据表名
     *
     * @var string
     */
    protected string $table;

    /**
     * 数据表别名
     *
     * @var string
     */
    protected string $alias;

    /**
     * 表字段
     *
     * @var array
     */
    protected array $columns = [];

    /**
     * sql语句,用于直接查询
     *
     * @var string
     */
    protected string $sql = '';

    /**
     * 是否去重
     *
     * @var boolean
     */
    protected bool $distinct = false;

    /**
     * 查询字段
     *
     * @var string
     */
    protected string $fields = '*';

    /**
     * join状态
     *
     * @var array
     */
    protected array $join = [];

    /**
     * union状态
     *
     * @var array
     */
    protected array $union = [];

    /**
     * 查询条件
     *
     * @var string
     */
    protected string $condition = '';

    /**
     * 查询参数
     *
     * @var array
     */
    protected array $args = [];

    /**
     * 分组条件
     *
     * @var string
     */
    protected string $group = '';

    /**
     * having参数
     *
     * @var array
     */
    protected array $having = [];

    /**
     * 排序规则
     *
     * @var string
     */
    protected string $order = '';

    /**
     * 查询指定记录数
     *
     * @var array
     */
    protected array $limit = [];

    /**
     * @param string $table
     * @param ContainerInterface|null $container
     * @throws \Polaris\Exception
     */
    public function __construct(string $table, ?ContainerInterface $container = null)
    {
        parent::__construct($container);
        $this->table($table);
    }

    /**
     * 设置/获取表名
     * <code>
     * var_dump($this->table('User t1')->field('t1.id')->fetchColumn());
     * </code>
     *
     * @param string|null $table
     * @return string|static
     */
    public function table(?string $table = null)
    {
        if (is_null($table)) {
            return strcmp($this->table, $this->alias) ? "{$this->table} {$this->alias}" : $this->table;
        } else {
            $table = array_filter(explode(' ', $table));
            $this->table = $table[0] ?? null;
            $this->alias = $table[1] ?? $table[0];
            return $this;
        }
    }

    /**
     * 设置/返回字段列表
     *
     * @param mixed $columns
     * @return array|static
     */
    public function columns($columns = null)
    {
        if (is_null($columns)) {
//			if (empty($this->columns) && $this->driver == Db::DIRECT) {
//				$this->columns = array_keys(parent::columns($this->table));
//			}
            return $this->columns;
        } else {
            $this->columns = $columns;
            return $this;
        }
    }

    /**
     * 设置查询查询去重
     *
     * @param boolean|null $distinct 是否去重
     * @return boolean|static
     */
    public function distinct(bool $distinct = null)
    {
        if (is_null($distinct)) {
            return $this->distinct;
        } else {
            $this->distinct = $distinct;
            return $this;
        }
    }

    /**
     * 设置查询返回的字段列表
     *
     * @param mixed $fields 字段列表
     * @return string|static
     */
    public function fields($fields = null)
    {
        if (is_null($fields)) {
            return $this->fields;
        } else {
            $this->fields = is_array($fields) ? implode(', ', $fields) : trim($fields);
            return $this;
        }
    }

    /**
     * 设置join条件
     *
     * @param string $table 表名
     * @param string|mixed $on 条件
     * @param mixed $fields 列出字段
     * @param string|null $type join类型
     * @return static
     */
    public function join(string $table, $on, $fields = null, string $type = null): self
    {
        if (empty($type)) {
            $type = static::LeftJoin;
        }
        if (is_array($on)) {
            list($on, $args) = $on;
        } else {
            $args = [];
        }
        $fields = trim($fields);
        $this->join[] = compact('table', 'on', 'args', 'fields', 'type');
        return $this;
    }

    /**
     * 设置union
     *
     * @param mixed $table union对象
     * @param mixed $args 参数
     * @param boolean $all 是否为union all
     * @return static
     */
    public function union($table, $args = null, bool $all = false): self
    {
        $all = $all ? 'ALL' : '';
        if (is_string($table) && !empty($table)) {
            $this->union[] = compact('table', 'args', 'all');
        }
        return $this;
    }

    /**
     * 设置查询条件和参数
     *
     * @param mixed $condition 查询条件
     * @return static
     */
    public function where($condition): self
    {
        if (!empty($condition)) {
            if (!is_array($condition)) {
                $condition = [$condition, []];
            }

            if (!isset($condition[0])) {
                $data = $condition;
                $condition = ['', []];
                foreach ($data as $k => $v) {
                    $condition[0] .= $condition[0] ? ' AND ' : '';
                    if (is_null($v)) {
                        $condition[0] .= "{$k} IS NULL";
                    } else {
                        $condition[0] .= "{$k} = ?";
                        $condition[1][] = $v;
                    }
                }
            }
            list($this->condition, $this->args) = $condition;
            $this->args = (array)$this->args;
        }
        return $this;
    }

    /**
     * 为查询设置分组条件
     *
     * @param string $group 分组条件
     * @return static
     */
    public function group(string $group): self
    {
        $this->group = trim($group);
        return $this;
    }

    /**
     * 设置分组having
     *
     * @param mixed $condition 条件
     * @return static
     */
    public function having($condition): self
    {
        if (is_array($condition)) {
            list($condition, $args) = $condition;
        } else {
            $args = [];
        }
        $this->having = [trim($condition), is_array($args) ? $args : [$args]];
        return $this;
    }

    /**
     * 设置排序规则
     *
     * @param string $rule 排序规则
     * @return static
     */
    public function order(string $rule): self
    {
        $this->order = trim($rule);
        return $this;
    }

    /**
     * 设置查询记录区间
     *
     * @param integer $rows 取出数量
     * @param integer $offset 偏移量
     * @return static
     */
    public function limit(int $rows, int $offset = 0): self
    {
        if ($rows) {
            $this->limit = array_map('intval', compact('rows', 'offset'));
        } else {
            $this->limit = [];
        }
        return $this;
    }

    /**
     * 执行查询
     *
     * <p>若$exec为不为true时返回sql语句和执行参数</p>
     *
     * @param boolean $exec
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function select(bool $exec = true): ?array
    {
        if (empty($this->sql)) {
            $distinct = $this->distinct ? 'DISTINCT' : '';
            $fields = $this->fields == '*' ? ("{$this->alias}.*") : $this->fields;
            $join = '';
            if (!empty($this->join)) {
                $args = [];
                foreach ($this->join as $item) {
                    $join .= " {$item['type']} {$item['table']} ON {$item['on']}";
                    if (!empty($item['fields'])) {
                        $fields .= $fields ? ',' : '';
                        $fields .= $item['fields'];
                    }
                    if (!empty($item['args'])) {
                        if (is_array($item['args'])) {
                            $args = array_merge($args, $item['args']);
                        } else {
                            $args[] = $item['args'];
                        }
                    }
                }
                $this->args = array_merge($args, $this->args);
            }

            $condition = $this->condition ? " WHERE {$this->condition}" : '';

            $group = $this->group ? " GROUP BY {$this->group}" : '';

            $having = '';
            if ($this->having) {
                $having = "HAVING {$this->having[0]}";
                if (isset($this->having[1]) && !empty($this->having[1])) {
                    $this->args = array_merge($this->args, $this->having[1]);
                }
            }

            $order = $this->order ? " ORDER BY {$this->order}" : '';

            $limit = $this->limit ? " LIMIT {$this->limit['offset']},{$this->limit['rows']}" : '';

            $union = '';
            if ($this->union) {
                foreach ($this->union as $item) {
                    $union .= " UNION {$item['all']} {$item['table']} ";
                    if ($item['args']) {
                        $this->args = array_merge($this->args, is_array($item['args']) ? $item['args'] : [$item['args']]);
                    }
                }
            }
            $table = $this->table();
            $this->sql = "SELECT {$distinct} {$fields} FROM {$table} {$join} {$condition} {$group} {$having} {$order} {$limit} {$union}";
        }

        if ($exec) {
            $statement = $this->query($this->sql, $this->args);
            $this->destruct();
            return $statement;
        } else {
            return ['sql' => $this->sql, 'args' => $this->args];
        }
    }

    /**
     * 插入数据
     *
     * <code>
     * //插入一条数据
     * var_dump($this->insert(['title'=>'database']));//返回自增id或执行状态
     *
     * //插入多条数据
     * var_dump($this->insert([
     *       ['title'=>'database'],
     *     ['title'=>'development'],
     *     ['title'=>'framework']
     * ]), true);
     *
     * //插入原始数据(插入数据会在解析时处理成预处理语句和参数,若希望写入原始数据则使用如下方法)
     * var_dump($this->insert([
     *     'title' => 'database',
     *     'time' => ['NOW()'] //使用sql内置函数
     * ]));
     * </code>
     *
     * @param array $data
     * @param bool $multi
     * @param mixed $duplicate
     * @return bool|string
     * @throws \Polaris\Exception
     */
    public function insert(array $data, bool $multi = false, $duplicate = false)
    {
        if (empty($data)) {
            return false;
        }

        if (!$multi) {
            return $this->insert([$data], true, $duplicate);
        }

        $fields = '';
        $args = [];
        $values = '';

        foreach ($data as $k => $v) {
            if (!$k) {
                $fields = '(`' . implode('`,`', array_keys($v)) . '`)';
                if ($duplicate && !is_array($duplicate)) {
                    $duplicate = array_combine(array_keys($v), array_map(function ($v) {
                        return ['VALUES(`' . $v . '`)'];
                    }, array_keys($v)));
                }
            }
            $values .= $values ? ',' : '';
            $values .= '(' . implode(', ', array_fill(0, sizeof($v), '?')) . ')';
            $args = array_merge($args, array_values($v));
        }

        if ($duplicate) {
            $args = array_merge($args, array_values($duplicate));
            $duplicate = "ON DUPLICATE KEY UPDATE " . implode(', ', array_map(function ($v) {
                    return "`{$v}` = ?";
                }, array_keys($duplicate)));
        }

        $this->execute("INSERT INTO {$this->table} {$fields} VALUES {$values} {$duplicate}", $args);
        return $this->getConnection()->lastInsertId();
    }

    /**
     * 更新数据
     *
     * <code>
     * //更新id大于5的记录中的time为当前时间
     * var_dump($this->where('id > ?', 5)->update(['time'=>['NOW()']]));
     * </code>
     *
     * @param array $data 要更新的数据
     * @param bool $force 是否强制更新(即是否允许无条件更新)
     * @return integer
     * @throws \Polaris\Exception
     */
    public function update(array $data, bool $force = false)
    {
        if (empty($this->condition) && !$force) {
            return false;
        }

        $join = '';
        if (!empty($this->join)) {
            $args = [];
            foreach ($this->join as $item) {
                $join .= " {$item['type']} {$item['table']} ON {$item['on']}";
                if (is_array($item['args'])) {
                    $args = array_merge($args, $item['args']);
                } else {
                    $args[] = $item['args'];
                }
            }
            $this->args = array_merge($args, $this->args);
        }

        $set = '';
        $args = [];
        foreach ($data as $field => $value) {
            $set .= $set ? ',' : '';
            if (is_null($value)) {
                $set .= " `{$field}` = NULL ";
            } else {
                $set .= " `{$field}` = ? ";
                $args[] = $value;
            }
        }
        $table = $this->table();
        $sql = "UPDATE {$table} {$join} SET {$set} ";
        if ($this->condition) {
            $sql .= " WHERE {$this->condition}";
        }
        $rows = $this->execute($sql, array_merge($args, $this->args));
        $this->destruct();
        return $rows;
    }

    /**
     * 删除数据
     *
     * <code>
     * //删除id大于5的记录
     * var_dump($this->where('id > ?', 5)->delete());
     * </code>
     *
     * @param bool $force 是否强制删除(即是否允许无条件删除)
     * @return integer
     * @throws \Polaris\Exception
     */
    public function delete(bool $force = false)
    {
        if (empty($this->condition) && !$force) {
            return false;
        }

        $tables = [$this->table];
        $join = '';
        if (!empty($this->join)) {
            $args = [];
            foreach ($this->join as $item) {
                $join .= " {$item['type']} `{$item['table']}` ON {$item['on']}";
                $tables[] = "{$item['table']}";
                if (is_array($item['args'])) {
                    $args = array_merge($args, $item['args']);
                } else {
                    $args[] = $item['args'];
                }
            }
            $this->args = array_merge($args, $this->args);
        }
        $tables = implode(',', $tables);
        $table = $this->table();
        $sql = "DELETE {$tables} FROM {$table} {$join} WHERE {$this->condition}";
        $rows = $this->execute($sql, $this->args);
        $this->destruct();
        return $rows;
    }

    /**
     * 获取所有记录
     *
     * @param mixed $condition
     * @param int $rows
     * @param int $start
     * @param string $order
     * @param mixed $fields
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function findAll($condition = null, int $rows = 0, int $start = 0, string $order = '', $fields = '*'): ?array
    {
        return $this->fields($fields)->where($condition)->order($order)->limit($rows, $start)->select();
    }

    /**
     * 获取一条记录
     *
     * @param mixed $condition
     * @param mixed $fields
     * @param string $order
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function find($condition, $fields = '*', string $order = ''): ?array
    {
        $result = $this->findAll($condition, 1, 0, $order, $fields);
        if ($result) {
            reset($result);
            return current($result);
        } else {
            return $result;
        }
    }

    /**
     * @param mixed $condition
     * @param mixed $fields
     * @param string $order
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function findOrFail($condition, $fields = '*', string $order = ''): ?array
    {
        $result = $this->find($condition, $fields, $order);
        if (empty($result)) {
            throw new NoRecordException();
        }
        return $result;
    }

    /**
     * 获取一条记录的第一个字段
     *
     * @param mixed $condition
     * @param string $column
     * @param string $order
     * @return mixed
     * @throws \Polaris\Exception
     */
    public function findColumn($condition, string $column, string $order = '')
    {
        $result = $this->find($condition, $column, $order);
        if ($result) {
            reset($result);
            return current($result);
        } else {
            return $result;
        }
    }

    /**
     * 取出并组合数据
     *
     * @param string|null $value
     * @param string|null $offset
     * @param mixed $condition
     * @param int $rows
     * @param int $start
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function findPair(?string $value = null, string $offset = null, $condition = null, int $rows = 0, int $start = 0): ?array
    {
        $result = $this->findAll($condition, $rows, $start, '', is_null($value) ? '*' : trim("{$offset}, {$value}", ','));
        $offset = strpos($offset, '.') !== false ? substr($offset, strpos($offset, '.') + 1) : $offset;
        $value = strpos($value, '.') !== false ? substr($value, strpos($value, '.') + 1) : $value;
        return $result ? array_column($result, $value, $offset) : $result;
    }

    /**
     * 查找或创建
     *
     * @param $condition
     * @param array $data
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function firstOrCreate($condition, array $data = []): ?array
    {
        $first = $this->find($condition);
        if (!empty($first)) {
            return $first;
        }
        $this->insert(array_merge(!is_numeric(key($condition)) ? $condition : [], $data));
        return $this->find($condition);
    }

    /**
     * 创建或更新
     *
     * @param mixed $condition
     * @param array $data
     * @return array|null
     * @throws \Polaris\Exception
     */
    public function createOrUpdate($condition, array $data = []): ?array
    {
        if (!$this->find($condition)) {
            $this->insert(array_merge(!is_numeric(key($condition)) ? $condition : [], $data));
        } else {
            $this->where($condition)->update($data);
        }
        return $this->find($condition);
    }

    /**
     * 必须查找到某条数据
     *
     * @param mixed $condition
     * @return array
     * @throws \Polaris\Exception
     */
    public function firstOrFail($condition): array
    {
        $exists = $this->find($condition);
        if (!$exists) {
            throw new NoRecordException();
        }
        return $exists;
    }

    /**
     * 查询总记录数
     *
     * @param mixed $condition
     * @return integer
     * @throws \Polaris\Exception
     */
    public function count($condition = null): int
    {
        return intval($this->findColumn($condition, 'COUNT(1)'));
    }

    /**
     * @param int $page
     * @param int $size
     * @param mixed $condition
     * @param string $orders
     * @param mixed $fields
     * @return array
     * @throws \Polaris\Exception
     */
    public function getList(int $page = 1, int $size = 10, $condition = null, string $orders = '', $fields = '*'): array
    {
        $clone = clone $this;
        $total = $clone->count($condition);
        list($page, $size) = [gmp_strval(gmp_intval($page)), gmp_strval(gmp_intval($size))];
        $offset = gmp_strval(gmp_abs(gmp_mul(gmp_sub($page, 1), $size)));
        $pages = intval($total / $size) + (($total % $size) ? 1 : 0);
        $list = $this->findAll($condition, $size, $offset, $orders, $fields);
        $current = $total ? $page : 0;
        return compact('list', 'total', 'current', 'pages');
    }

    /**
     * 清除操作状态
     */
    protected function destruct()
    {
        $this->sql = '';
        $this->distinct = false;
        $this->fields = '*';
        $this->join = [];
        $this->condition = '';
        $this->args = [];
        $this->group = '';
        $this->having = [];
        $this->order = '';
        $this->limit = [];
    }

}