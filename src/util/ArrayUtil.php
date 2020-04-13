<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/10/27
 * Time: 上午9:04
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;

/**
 * 数组工具类
 */
class ArrayUtil {

    /**
     * 检查数组是否是 list 类型的
     *
     * @param  array $value
     * @return  bool
     */
    public static function isList($value): bool {
        return is_array($value) && (!$value || array_keys($value) === range(0, count($value) - 1));
    }


    /**
     * 分组
     *
     * @param array  $list         数组
     * @param string $parent_field 按某个字段分组
     *
     * @return array
     */
    public static function groupBy($list, $parent_field = 'parent_id') {
        $map = [];
        foreach ($list as $item) {
            $parent_id = null;
            $parent_id = $item[ $parent_field ];
            if (!$parent_id) {
                $parent_id = "_";
            }
            $map[ $parent_id ][] = $item;
        }
        return $map;
    }

    /**
     * 唯一分组
     *
     * @param  array $list         数组
     * @param string $parent_field 某个字段
     *
     * @return array
     */
    public static function indexBy($list, $parent_field = 'parent_id') {
        $map = [];
        foreach ($list as $item) {
            $parent_id = null;
            $parent_id = $item[ $parent_field ];
            if (!$parent_id) {
                $parent_id = "_";
            }
            $map[ $parent_id ] = $item;
        }
        return $map;
    }

    /**
     * 转化为树形
     *
     * @param array  $list           数组
     * @param string $parent_field   父字段
     * @param string $id_field       主键字段
     * @param string $children_field 子字段
     *
     * @return array
     */
    public static function toTree($list, $parent_field = 'parent_id', $id_field = 'id', $children_field = 'children') {
        $map = self::groupBy($list);
        $data = [];
        foreach ($list as $item) {
            $item->text = $item->name;
            if (is_object($item)) {
                if (!$item->$parent_field) {
                    $item->$children_field = $map[ $item->$id_field ];
                    $data[] = $item;
                }
            }
        }
        return $data;
    }

    /**
     * 查找一个
     *
     * @param array          $list  数组
     * @param \Closure|array $where 条件数组或方法
     *
     * @return null
     */
    public static function find($list, $where) {
        if (is_array($where)) {
            foreach ($list as $item) {
                $is_ok = true;
                foreach ($where as $key => $value) {
                    if ($item[ $key ] != $value) {
                        $is_ok = false;
                        break;
                    }
                }
                if ($is_ok) {
                    return $item;
                }
            }
        } else {
            foreach ($list as $item) {
                if ($where($item)) {
                    return $item;
                }
            }
        }
        return null;
    }


    /**
     * where 查找符合条件的
     *
     * @param array          $list  数组
     * @param \Closure|array $where $where 条件数组或方法
     *
     * @return array
     */
    public static function where($list, $where) {
        $items = [];
        if (is_array($where)) {
            foreach ($list as $item) {
                $is_ok = true;
                foreach ($where as $key => $value) {
                    if ($item[ $key ] != $value) {
                        $is_ok = false;
                        break;
                    }
                }
                if ($is_ok) {
                    $items[] = $item;
                }
            }
        } else {
            foreach ($list as $item) {
                if ($where($item)) {
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    /**
     * 摘取某个字段的值,返回为数组
     *
     * @param array  $list 数组
     * @param string $name 需要摘取的字段
     *
     * @return array
     */
    public static function pluck($list, $name) {
        $values = [];
        foreach ($list as $item) {
            $values[] = $item[ $name ];
        }
        return $values;
    }
}
