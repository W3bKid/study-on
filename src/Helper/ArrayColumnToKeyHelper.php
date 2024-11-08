<?php

namespace App\Helper;

class ArrayColumnToKeyHelper
{
    public static function mapToKey($array, $column): array
    {
        $result = [];
        foreach ($array as $obj) {
            $result[$obj[$column]] = $obj;
        }

        return $result;
    }
}