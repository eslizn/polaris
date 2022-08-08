<?php

namespace Polaris\Database\Traits;

/**
 *
 */
trait TouchTrait
{

    /**
     * @param array $data
     * @param bool $multi
     * @param mixed $duplicate
     * @return array
     */
    protected function beforeInsertTouch(array $data, bool $multi, $duplicate = false): array
    {
        if ($multi) {
            foreach ($data as $k => $v) {
                if (!isset($v[$this->created_at ?? 'created_at'])) {
                    $data[$k][$this->created_at ?? 'created_at'] = ['NOW()'];
                }
                if (!isset($v[$this->updated_at ?? 'updated_at'])) {
                    $data[$k][$this->updated_at ?? 'updated_at'] = ['NOW()'];
                }
            }
        }
        return [$data, $multi, $duplicate];
    }

    /**
     * @param array $data
     * @param bool $force
     * @return array
     */
    protected function beforeUpdateTouch(array $data, bool $force): array
    {
        if (!isset($data[$this->updated_at ?? 'updated_at'])) {
            $data[$this->updated_at ?? 'updated_at'] = ['NOW()'];
        }
        return [$data, $force];
    }

}