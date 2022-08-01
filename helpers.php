<?php

if (!function_exists('array_only')) {
    /**
     * @param array $array
     * @param mixed $keys
     * @return array
     */
    function array_only(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}


