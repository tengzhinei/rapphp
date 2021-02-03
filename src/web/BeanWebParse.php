<?php


namespace rap\web;


interface BeanWebParse {

    public function parseRequest(Request $request);

    /**
     * 获取通过 request 创建时摘取的字段
     * 默认返回同toJsonField相同
     * return '字段1,字段2' 或return ['字段1,字段2',false]//反向字段
     * @return string|array
     */
    public function requestField();


    /**
     * 获取转换为json时的字段
     * 默认返回所有 public的字段
     * return '字段1,字段2' 或return ['字段1,字段2',false]//反向字段
     * @return string|array
     */
    public function toJsonField();
}