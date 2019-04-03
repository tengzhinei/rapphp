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

    static function groupBy($list, $parent_field = 'parent_id') {
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

    static function indexBy($list, $parent_field = 'parent_id') {
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
     * @param        $list
     * @param string $parent_field
     * @param string $id_field
     * @param string $children_field
     *
     * @return array
     */
    static  function toTree($list,$parent_field = 'parent_id',$id_field='id',$children_field='children') {
        $map = self::groupBy($list);
        $data=[];
        foreach ($list as $item) {
            $item->text=$item->name;
            if(is_object($item)){
                if(!$item->$parent_field){
                    $item->$children_field=$map[$item->$id_field];
                    $data[]=$item;
                }
            }
        }
        return $data;
    }

    /**
     * @param array          $list
     * @param \Closure|array $where
     *
     * @return null
     */
    static  function find($list, $where) {
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


    static function where($list, $where) {
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

   static function pluck($list, $name) {
        $values = [];
        foreach ($list as $item) {
            $values[] = $item[ $name ];
        }
        return $values;
    }


}