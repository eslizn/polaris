<?php

namespace Polaris\Database\Traits;

use Polaris\Database\Exception;

/**
 *
 * @method static array firstOrCreate($condition, array $data = [])
 * @method static array createOrUpdate($condition, array $data = [])
 * @method static array firstOrFail($condition)
 * @method static static table(string $table = null)
 * @method static static columns($columns = null)
 * @method static static distinct(bool $distinct = null)
 * @method static static fields($fields = null)
 * @method static static join(string $table, $on, $fields = null, string $type = null)
 * @method static static union($table, $args = null, bool $all = false)
 * @method static static where($condition)
 * @method static static group(string $group)
 * @method static static having($condition)
 * @method static static order(string $rule)
 * @method static static limit(int $rows, int $offset = 0)
 * @method static mixed select(bool $exec = true)
 * @method static string|bool insert(array $data, bool $multi = false, $duplicate = false)
 * @method static int|bool update(array $data, bool $force = false)
 * @method static int|bool delete(bool $force = false)
 * @method static array findAll($condition = null, int $rows = 0, int $start = 0, string $order = '', $fields = '*')
 * @method static array find($condition, $fields = '*', string $order = '')
 * @method static array findOrFail($condition, $fields = '*', string $order = '')
 * @method static array findColumn($condition, string $column, string $order = '')
 * @method static array findPair(string $value, string $offset = null, $condition = null, int $rows = 0, int $start = 0)
 * @method static int count($condition = null)
 * @method static array getList(int $page = 1, int $size = 10, $condition = null, string $orders = '', $fields = '*')
 */
trait FactoryTrait
{

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic(string $method, array $args)
    {
        if (!method_exists(get_called_class(), $method)) {
            throw new Exception(sprintf('Call to undefined method %s::%s()', get_called_class(), $method));
        }
        return call_user_func_array([new static(), $method], $args);
    }

}