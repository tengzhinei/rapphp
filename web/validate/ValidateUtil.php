<?php
namespace rap\web\validate;

use rap\storage\File;
use rap\util\Lang;

/**
 * User: jinghao@duohuo.net
 * Date: 18/9/8
 * Time: 下午11:28
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
class ValidateUtil {

    private $value;
    private $as_name;
    private $is_throw;
    public  $isValidate;
    private $msg;

    public static function param($value, $as_name, $is_throw = true) {
        $validate = new static();
        $validate->value = $value;
        $validate->as_name = $as_name;
        $validate->is_throw = $is_throw;
        return $validate;
    }


    public static function request($name, $as_name = '', $is_throw = true) {
        $value = request()->param($name);
        if (!$as_name) {
            $as_name = $name;
        }
        $validate = new static();
        $validate->value = $value;
        $validate->as_name = $as_name;
        $validate->is_throw = $is_throw;
        return $validate;
    }

    /**
     * 正在检查的类型
     * @var string
     */
    private $check_role = '';


    /**
     * 必须
     * @return $this
     */
    public function required() {
        $this->check_role = 'require';
        $result = !empty($this->value) || '0' == $this->value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 接受 ['1', 'on', 'yes']
     * @return $this
     */
    public function isAccepted() {
        $this->check_role = 'accepted';
        $result = in_array($this->value, ['1', 'on', 'yes']);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 是否是一个有效日期
     * @return $this
     */
    public function isDate() {
        $this->check_role = 'date';
        $result = false !== strtotime($this->value);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许字母
     * @return $this
     */
    public function isAlpha() {
        $this->check_role = 'alpha';
        $result = $this->regex($this->value, '/^[A-Za-z]+$/');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许字母和数字
     * @return $this
     */
    public function isAlphaNum() {
        $this->check_role = 'alphaNum';
        $result = $this->regex($this->value, '/^[A-Za-z0-9]+$/');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许字母、数字和下划线 破折号
     * @return $this
     */
    public function isAlphaDash() {
        $this->check_role = 'alphaDash';
        $result = $this->regex($this->value, '/^[A-Za-z0-9\-\_]+$/');
        $this->checkResult($result);
        return $this;
    }

    public function isChs() {
        $this->check_role = 'chs';
        $result = $this->regex($this->value, '/^[\x{4e00}-\x{9fa5}]+$/u');
        $this->checkResult($result);
        return $this;
    }


    public function isChsAlpha() {
        $this->check_role = 'chsAlpha';
        $result = $this->regex($this->value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u');
        $this->checkResult($result);
        return $this;
    }

    public function isChsAlphaNum() {
        $this->check_role = 'chsAlphaNum';
        $result = $this->regex($this->value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u');
        $this->checkResult($result);
        return $this;
    }

    public function isChsDash() {
        $this->check_role = 'chsAlphaNum';
        $result = $this->regex($this->value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u');
        $this->checkResult($result);
        return $this;
    }

    public function activeUrl() {
        $this->check_role = 'activeUrl';
        $result = checkdnsrr($this->value);
        $this->checkResult($result);
        return $this;
    }


    public function url() {
        $this->check_role = 'url';
        $result = $this->filter($this->value, FILTER_VALIDATE_URL);
        $this->checkResult($result);
        return $this;
    }

    public function float() {
        $this->check_role = 'float';
        $result = $this->filter($this->value, FILTER_VALIDATE_FLOAT);
        $this->checkResult($result);
        return $this;
    }

    public function number() {
        $this->check_role = 'number';
        $result = is_numeric($this->value);
        $this->checkResult($result);
        return $this;
    }

    public function integer() {
        $this->check_role = 'integer';
        $result = $this->filter($this->value, FILTER_VALIDATE_INT);
        $this->checkResult($result);
        return $this;
    }

    public function email() {
        $this->check_role = 'email';
        $result = $this->filter($this->value, FILTER_VALIDATE_EMAIL);
        $this->checkResult($result);
        return $this;
    }

    public function boolean() {
        $this->check_role = 'email';
        $result = in_array($this->value, [true, false, 0, 1, '0', '1'], true);
        $this->checkResult($result);
        return $this;
    }

    public function isArray() {
        $this->check_role = 'array';
        $result = is_array($this->value);
        $this->checkResult($result);
        return $this;
    }

    public function file() {
        $this->check_role = 'file';
        $result = false;
        if ($this->value instanceof File) {
            $result = is_file($this->value->path_tmp);
        }
        $this->checkResult($result);
        return $this;
    }

    public function fileExt($ext) {

    }

    public function fileMime() {

    }

    public function fileSize($size) {
        $this->check_role = 'fileSize';
        $result = false;
        if ($this->value instanceof File) {
            $result = filesize($this->value->path_tmp) <= $size;
        }
        $this->checkResult($result);
        return $this;
    }


    public function image() {
        $this->check_role = 'image';
        $result = $this->value instanceof File && in_array($this->getImageType($this->value->path_tmp), [1, 2, 3, 6]);
        $this->checkResult($result);
        return $this;
    }


    // 判断图像类型
    protected function getImageType($image) {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        } else {
            try {
                $info = getimagesize($image);
                return $info ? $info[ 2 ] : false;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * 验证是否和某个字段的值一致
     * @access protected
     *
     * @param mixed $value 字段值
     *
     * @return $this
     */
    public function confirm($value) {
        $this->check_role = 'confirm';
        $result = $this->value == $value;
        $this->checkResult($result);
        return $this;
    }

    public function different($value) {
        $this->check_role = 'different';
        $result = $this->value != $value;
        $this->checkResult($result);
        return $this;
    }

    public function egt($value) {
        $this->check_role = 'egt';
        $result = !is_null($this->value) && $this->value >= $value;
        $this->checkResult($result);
        return $this;
    }

    protected function gt($value) {
        $this->check_role = 'gt';
        $result = !is_null($this->value) && $this->value > $value;
        $this->checkResult($result);
        return $this;
    }

    public function elt($value) {
        $this->check_role = 'elt';
        $result = !is_null($this->value) && $this->value <= $value;
        $this->checkResult($result);
        return $this;
    }

    public function lt($value) {
        $this->check_role = 'elt';
        $result = !is_null($this->value) && $this->value < $value;
        $this->checkResult($result);
        return $this;
    }

    public function eq($value) {
        $this->check_role = 'eq';
        $result = !is_null($this->value) && $this->value == $value;
        $this->checkResult($result);
        return $this;
    }


    public function dateFormat($value, $rule) {
        $this->check_role = 'eq';
        $info = date_parse_from_format($rule, $value);
        $result = 0 == $info[ 'warning_count' ] && 0 == $info[ 'error_count' ];
        $this->checkResult($result);
        return $this;
    }

    public function requireIfEq($key,$value){
        $this->check_role = 'require';
        $result=true;
        if($key==$value){
            $result = !empty($this->value) || '0' == $this->value;
        }
        $this->checkResult($result);
    }

    public function requireWhen(\Closure $closure){
        $need=$closure();
        $this->check_role = 'require';
        $result=true;
        if($need){
            $result = !empty($this->value) || '0' == $this->value;
        }
        $this->checkResult($result);
    }

    public function requireWith($value){
        $this->check_role = 'require';
        $result=true;
        if($value){
            $result = !empty($this->value) || '0' == $this->value;
        }
        $this->checkResult($result);
    }


    public function in($rule){
        $this->check_role = 'in';
        $result = in_array($this->value, is_array($rule) ? $rule : explode(',', $rule));;
        $this->checkResult($result);
        return $this;
    }


    public function notIn($rule){
        $this->check_role = 'notIn';
        $result = !in_array($this->value, is_array($rule) ? $rule : explode(',', $rule));;
        $this->checkResult($result);
        return $this;
    }


    public function between($min, $max)
    {
        $this->check_role = 'between';
        $result = $this->value >= $min && $this->value <= $max;
        $this->checkResult($result);
        return $this;
    }



    public function notBetween($min, $max)
    {
        $this->check_role = 'between';
        $result = $this->value < $min || $this->value > $max;
        $this->checkResult($result);
        return $this;
    }

    public function length($min, $max)
    {
        $this->check_role = 'between';
        $result = $this->value < $min || $this->value > $max;
        $this->checkResult($result);
        return $this;
    }


    /**
     * 使用正则验证数据
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则 正则规则或者预定义正则名
     *
     * @return mixed
     */
    protected function regex($value, $rule) {
        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            // 不是正则表达式则两端补上/
            $rule = '/^' . $rule . '$/';
        }
        return is_scalar($value) && 1 === preg_match($rule, (string)$value);
    }


    /**
     * 使用filter_var方式验证
     * @access protected
     *
     * @param mixed $value 字段值
     * @param mixed $rule  验证规则
     *
     * @return bool
     */
    protected function filter($value, $rule) {
        if (is_string($rule) && strpos($rule, ',')) {
            list($rule, $param) = explode(',', $rule);
        } elseif (is_array($rule)) {
            $param = isset($rule[ 1 ]) ? $rule[ 1 ] : null;
            $rule = $rule[ 0 ];
        } else {
            $param = null;
        }
        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }

    /**
     * 检查结果是否合格
     *
     * @param       $result
     * @param array $vars
     *
     * @throws ValidateException
     */
    private function checkResult($result, $vars = []) {
        if (!$result) {
            $this->isValidate = false;
            array_unshift($vars, $this->as_name);
            $msg = Lang::get('validate', $this->check_role, $vars);
            if ($this->is_throw) {
                throw new ValidateException($msg, 100010, null);
            } else {
                $this->msg = $msg;
            }
        }
    }


}