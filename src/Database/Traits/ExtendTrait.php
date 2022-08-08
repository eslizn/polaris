<?php

namespace Polaris\Database\Traits;

use Polaris\Database\Exception;

/**
 *
 */
trait ExtendTrait
{

    /**
     * @param bool $exec
     * @return array|mixed|string
     * @throws Exception
     */
    public function select(bool $exec = true): ?array
    {
        foreach ($this->search('beforeSelect') as $method) {
            $result = $this->$method($exec);
            if (!is_null($result)) {
                return $result;
            }
        }
        $result = parent::select($exec);
        foreach ($this->search('afterSelect') as $method) {
            $result = $this->$method($result);
        }
        return $result;
    }

    /**
     * @param array $data
     * @param bool $force
     * @return mixed
     * @throws Exception
     */
    public function update(array $data, bool $force = false)
    {
        foreach ($this->search('beforeUpdate') as $method) {
            $result = $this->$method($data, $force);
            if (!is_array($result)) {
                return $result;
            }
            list($data, $force) = $result;
        }
        $result = parent::update($data, $force);
        foreach ($this->search('afterUpdate') as $method) {
            $result = $this->$method($result);
        }
        return $result;
    }

    /**
     * @param array $data
     * @param bool $multi
     * @param mixed $duplicate
     * @return mixed
     * @throws Exception
     */
    public function insert(array $data, bool $multi = false, $duplicate = false)
    {
        foreach ($this->search('beforeInsert') as $method) {
            $result = $this->$method($data, $multi, $duplicate);
            if (!is_array($result)) {
                return $result;
            }
            list($data, $multi, $duplicate) = $result;
        }
        $result = parent::insert($data, $multi, $duplicate);
        foreach ($this->search('afterInsert') as $method) {
            $result = $this->$method($result, $data, $multi, $duplicate);
        }
        return $result;
    }

    /**
     * @param string $prefix
     * @return array
     */
    private function search(string $prefix): array
    {
        $methods = [];
        foreach (get_class_methods($this) as $method) {
            if (stripos($method, $prefix) === 0) {
                $methods[] = $method;
            }
        }
        return $methods;
    }

}