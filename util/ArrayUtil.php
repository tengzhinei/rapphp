<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/10/27
 * Time: 上午9:04
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;


class ArrayUtil {

   static function groupBy($list, $parent_field = 'parent_id') {
        $map = [];
        foreach ($list as $item) {
            $parent_id = null;
            if (is_object($item)) {
                $parent_id = $item->$parent_field;
            } else {
                $parent_id = $item[ $parent_field ];
            }
            if (!$parent_id) {
                $parent_id = "_";
            }
            $map[ $parent_id ][] = $item;
        }
        return $map;
    }

    function indexBy($list, $parent_field = 'parent_id'){
        $map = [];
        foreach ($list as $item) {
            $parent_id = null;
            if (is_object($item)) {
                $parent_id = $item->$parent_field;
            } else {
                $parent_id = $item[ $parent_field ];
            }
            if (!$parent_id) {
                $parent_id = "_";
            }
            $map[ $parent_id ] = $item;
        }
        return $map;
    }


}