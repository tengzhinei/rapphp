<?php

namespace rap\util\bean;

use rap\util\ArrayUtil;

class BeanDefer
{


   
    public static function arrayDefer($list)
    {
        if (!$list) {
            return;
        }
        $defers = [];
        foreach ($list as $item) {
            $defers[] = $item->defer()->getDefers();
        }
        $list_count = count($list);
        $map_list = [];
        $frist = $defers[0];
        $count = count($frist);
        for ($i = 0; $i < $count; $i++) {
            $ids = [];
            /**@var $item ArrayDeferredAble * */
            for ($j = 0; $j < $list_count; $j++) {
                $item = $list[$j];
                $deferInfo = $defers[$j][$i];
                $ids[] = $deferInfo->field;
            }
            /**@var $deffer ArrayDefer * */
            $deferInfo = $frist[$i];
            $defer_list = $deferInfo->defer_list;
            $itemMap = $defer_list($ids);
            for ($j = 0; $j < $list_count; $j++) {
                $item = $list[$j];
                $deferInfo = $defers[$j][$i];
                $itemCal = $deferInfo->defer_item;
                $itemCal($itemMap[$deferInfo->field],$j);
            }

        }

    }
}
