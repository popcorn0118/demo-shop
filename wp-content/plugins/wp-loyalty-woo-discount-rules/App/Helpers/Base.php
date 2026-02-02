<?php

namespace Wlwd\App\Helpers;

use const WLRR\App\Helpers\ARRAY_A;

class Base
{
    public static function db()
    {
        global $wpdb;
        return $wpdb;
    }

    public static function fetchData($query, $type)
    {
        if (!is_string($query) || empty($query) || !is_string($type) || empty($type)) return [];
        switch ($type) {
            case $type == 'single':
                return self::db()->get_row($query);
            case $type == 'multiple':
                return self::db()->get_results($query);
            case $type == 'value':
                return self::db()->get_var($query);
            default :
                return [];
        }
    }


    public static function buildQuery($select = [], $table = '', $joins = '', $where = [], $groups = '', $order = '', $fetch_type = 'get_results')
    {
        if (!is_array($select) || empty($select) || !is_string($fetch_type) || !is_string($groups) || empty($fetch_type) || !is_string($table) || empty($table) || !is_string($joins) || !is_array($where) || !is_string($order)) return [];
        $select = implode(',', $select);
        $query = "SELECT {$select} FROM {$table}  {$joins} ";
        $query .= !empty($where) ? " WHERE " . implode(' AND ', $where) : '';
        $query .= $groups;
        $query .= "{$order};";
        return $query;
    }



}