<?php
namespace rap\web\validate;

use rap\db\Record;
use rap\storage\File;
use rap\util\Lang;

/**
 * User: jinghao@duohuo.net
 * Date: 18/9/8
 * Time: 下午11:28
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
class Validate {

    private $value;
    private $as_name;
    private $is_throw;
    public  $isValidate;
    private $msg;
    private $msg_check;

    private $p1;
    private $p2;
    private $rule;

    private function __construct() {
    }

    /**
     * @param $msg
     *
     * @return $this
     */
    public function msg($msg) {
        $this->msg_check = $msg;
        return $this;
    }

    /**
     * 检查参数
     *
     * @param string $value
     * @param string $as_name
     * @param bool   $is_throw
     *
     * @return \rap\web\validate\Validate
     */
    public static function param($value, $as_name = '', $is_throw = true) {
        $validate = new static();
        $validate->value = $value;
        $validate->as_name = $as_name;
        $validate->is_throw = $is_throw;
        return $validate;
    }

    /**
     * 检查参数
     *
     * @param string $name
     * @param string $as_name
     * @param bool   $is_throw
     *
     * @return \rap\web\validate\Validate
     */
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

    public function isTrue() {
        $this->check_role = 'require';
        $result = $this->value;
        $this->checkResult($result);
        return $this;
    }


    /**
     * 接受 ['1', 'on', 'yes']
     * @return $this
     */
    public function accepted() {
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
        $result = $this->checkRegex($this->value, '/^[A-Za-z]+$/');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许字母和数字
     * @return $this
     */
    public function isAlphaNum() {
        $this->check_role = 'alphaNum';
        $result = $this->checkRegex($this->value, '/^[A-Za-z0-9]+$/');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许字母、数字和下划线 破折号
     * @return $this
     */
    public function isAlphaDash() {
        $this->check_role = 'alphaDash';
        $result = $this->checkRegex($this->value, '/^[A-Za-z0-9\-\_]+$/');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许中文
     * @return $this
     */
    public function isChs() {
        $this->check_role = 'chs';
        $result = $this->checkRegex($this->value, '/^[\x{4e00}-\x{9fa5}]+$/u');
        $this->checkResult($result);
        return $this;
    }


    /**
     * 只允许中文字母
     * @return $this
     */
    public function isChsAlpha() {
        $this->check_role = 'chsAlpha';
        $result = $this->checkRegex($this->value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许中文字母数子
     * @return $this
     */
    public function isChsAlphaNum() {
        $this->check_role = 'chsAlphaNum';
        $result = $this->checkRegex($this->value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 只允许中文、字母、数字和下划线_及破折号-
     * @return $this
     */
    public function isChsDash() {
        $this->check_role = 'chsDash';
        $result = $this->checkRegex($this->value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u');
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查有效可访问的域名或ip
     * @return $this
     */
    public function activeUrl() {
        $this->check_role = 'activeUrl';
        $result = checkdnsrr($this->value);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 有效的网址
     * @return $this
     */
    public function url() {
        $this->check_role = 'url';
        $result = $this->filter($this->value, FILTER_VALIDATE_URL);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查浮点数
     * @return $this
     */
    public function float() {
        $this->check_role = 'float';
        $result = $this->filter($this->value, FILTER_VALIDATE_FLOAT);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查数字
     * @return $this
     */
    public function number() {
        $this->check_role = 'number';
        $result = is_numeric($this->value);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查整数
     * @return $this
     */
    public function integer() {
        $this->check_role = 'integer';
        $result = $this->filter($this->value, FILTER_VALIDATE_INT);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查邮箱
     * @return $this
     */
    public function email() {
        $this->check_role = 'email';
        $result = $this->filter($this->value, FILTER_VALIDATE_EMAIL);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查boolean
     * @return $this
     */
    public function boolean() {
        $this->check_role = 'boolean';
        $result = in_array($this->value, [true, false, 0, 1, '0', '1'], true);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查数组
     * @return $this
     */
    public function isArray() {
        $this->check_role = 'array';
        $result = is_array($this->value);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查文件
     * @return $this
     */
    public function file() {
        $this->check_role = 'file';
        if (!$this->value) {
            $result = false;
        } else if ($this->value instanceof File) {
            $result = is_file($this->value->path_tmp);
        } else {
            $result = is_file($this->value);
        }
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查文件后缀
     *
     * @param array $ext
     */
    public function fileExt($ext) {
        $this->check_role = 'fileExt';
        if (is_string($ext)) {
            $ext = explode(',', $ext);
        }
        $this->rule = implode(',', $ext);
        if (!$this->value) {
            $result = false;
        } else if ($this->value instanceof File) {
            $result = in_array(strtolower($this->value->ext), $ext);
        } else {
            $x = explode(".", $this->value);
            $result = in_array(strtolower($x[ count($x) - 1 ]), $ext);
        }
        $this->checkResult($result);
    }

    /**
     * 检查文件类型
     */
    public function fileMime($mime) {
        $this->check_role = 'fileMime';
        if (is_string($mime)) {
            $mime = explode(',', $mime);
        }
        $this->rule = implode(',', $mime);
        if (!$this->value) {
            $result = false;
        } else if (($this->value instanceof File)) {
            $file = File::fromRequest(['tmp_name' => $this->value]);
            $this->value = $file;
        }
        $result = in_array(strtolower($this->value->ext), $mime);
        $this->checkResult($result);
    }

    /**
     * 检查文件大小
     *
     * @param $size
     *
     * @return $this
     */
    public function fileSize($size) {
        $this->p1 = $size;
        $this->check_role = 'fileSize';
        if ($this->value instanceof File) {
            $result = filesize($this->value->path_tmp) <= $this->strToSize($size) ;
        } else {
            $result = filesize($this->value) <= $this->strToSize($size) ;
        }
        $this->checkResult($result);
        return $this;
    }


    /**
     * 检查是否图片
     * @return $this
     */
    public function image() {
        $this->check_role = 'image';
        if ($this->value instanceof File) {
            $result = in_array($this->getImageType($this->value->path_tmp), [1, 2, 3, 6]);
        } else {
            $result = in_array($this->getImageType($this->value), [1, 2, 3, 6]);
        }

        $this->checkResult($result);
        return $this;
    }


    /**
     * @param $image
     *
     * @return bool|int
     */
    private function getImageType($image) {
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
     * @param string $as_name 字段名称
     * @param mixed  $value   字段值
     *
     * @return $this
     */
    public function confirm($as_name, $value) {
        $this->p2 = $as_name;
        $this->check_role = 'confirm';
        $result = $this->value == $value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证是否和某个字段的值不同
     *
     * @param $as_name
     * @param $value
     *
     * @return $this
     */
    public function different($as_name, $value) {
        $this->p2 = $as_name;
        $this->check_role = 'different';
        $result = $this->value != $value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证是否大于等于
     *
     * @param $value
     *
     * @return $this
     */
    public function egt($value) {
        $this->rule = $value;
        $this->check_role = 'egt';
        $result = !is_null($this->value) && $this->value >= $value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证是否大于
     *
     * @param $value
     *
     * @return $this
     */
    public function gt($value) {
        $this->rule = $value;
        $this->check_role = 'gt';
        $result = !is_null($this->value) && $this->value > $value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证是否小于等于
     *
     * @param $value
     *
     * @return $this
     */
    public function elt($value) {
        $this->rule = $value;
        $this->check_role = 'elt';
        $result = !is_null($this->value) && $this->value <= $value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证是否小于
     *
     * @param $value
     *
     * @return $this
     */
    public function lt($value) {
        $this->rule = $value;
        $this->check_role = 'lt';
        $result = !is_null($this->value) && $this->value < $value;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证是否等于
     *
     * @param $value
     *
     * @return $this
     */
    public function eq($value) {
        $this->rule = $value;
        $this->check_role = 'eq';
        $result = !is_null($this->value) && $this->value == $value;
        $this->checkResult($result);
        return $this;
    }


    /**
     * 验证时间格式
     *
     * @param $value
     * @param $rule
     *
     * @return $this
     */
    public function dateFormat($value, $rule) {
        $this->rule = $rule;
        $this->check_role = 'dateFormat';
        $info = date_parse_from_format($rule, $value);
        $result = 0 == $info[ 'warning_count' ] && 0 == $info[ 'error_count' ];
        $this->checkResult($result);
        return $this;
    }

    /**
     * 当两个值相等时必须
     *
     * @param mixed $key   第一个值
     * @param mixed $value 第二个值
     */
    public function requireIfEq($key, $value) {
        $this->check_role = 'require';
        $result = true;
        if ($key == $value) {
            $result = !empty($this->value) || '0' == $this->value;
        }
        $this->checkResult($result);
    }

    /**
     * 当回调为真是必须
     *
     * @param \Closure $closure
     */
    public function requireWhen(\Closure $closure) {
        $need = $closure();
        $this->check_role = 'require';
        $result = true;
        if ($need) {
            $result = !empty($this->value) || '0' == $this->value;
        }
        $this->checkResult($result);
    }

    /**
     * 当另一个值存在时必须
     *
     * @param $value
     */
    public function requireWith($value) {
        $this->check_role = 'require';
        $result = true;
        if ($value) {
            $result = !empty($this->value) || '0' == $this->value;
        }
        $this->checkResult($result);
    }

    /**
     * 检查在范围内
     *
     * @param $rule
     *
     * @return $this
     */
    public function in($rule) {
        $this->check_role = 'in';
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        $this->rule = '[' . explode(',', $rule) . ']';
        $result = in_array($this->value, $rule);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 检查不在范围内
     *
     * @param $rule
     *
     * @return $this
     */
    public function notIn($rule) {
        $this->check_role = 'notIn';
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        $this->rule = '[' . explode(',', $rule) . ']';
        $result = !in_array($this->value, $rule);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 最小值
     *
     * @param $min
     *
     * @return $this
     */
    public function min($min) {
        $this->check_role = 'min';
        $this->p1 = $min;
        $result = $this->value >= $min;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 最大值
     *
     * @param $max
     *
     * @return $this
     */
    public function max($max) {
        $this->check_role = 'max';
        $this->p1 = $max;
        $result = $this->value <= $max;
        $this->checkResult($result);
        return $this;
    }


    /**
     * 在两值之间
     *
     * @param $min
     * @param $max
     *
     * @return $this
     */
    public function between($min, $max) {
        $this->check_role = 'between';
        $this->p1 = $min;
        $this->p2 = $max;
        $result = $this->value >= $min && $this->value <= $max;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 不在两值之间
     *
     * @param $min
     * @param $max
     *
     * @return $this
     */
    public function notBetween($min, $max) {
        $this->check_role = 'notBetween';
        $this->p1 = $min;
        $this->p2 = $max;
        $result = $this->value < $min || $this->value > $max;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 长度范围
     *
     * @param $min
     * @param $max
     *
     * @return $this
     */
    public function length($min, $max) {
        $this->check_role = 'length';
        $this->rule = "$min,$max";
        if (is_array($this->value)) {
            $length = count($this->value);
        } elseif ($this->value instanceof File) {
            $length = filesize($this->value->path_tmp);
        } else {
            $length = mb_strlen((string)$this->value);
        }
        $result = $length < $min || $length > $max;
        $this->checkResult($result);
        return $this;
    }

    /**
     * 符合正则
     * @param $rule
     *
     * @return $this
     */
    public function regex($rule) {
        $this->check_role = 'regex';
        $this->rule = $rule;
        $result = $this->checkRegex($this->value, $rule);
        $this->checkResult($result);
        return $this;
    }

    public function method($rule) {
        $this->check_role = 'method';
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        $method = request()->method();
        $result = in_array($method, $rule);
        $this->checkResult($result);
        return $this;
    }


    /**
     * 检查数据库是唯一值
     * @param $model
     * @param $field
     *
     * @return $this
     */
    public function unique($model, $field) {
        $this->check_role = 'unique';
        /* @var $model Record */
        $model = new $model;
        $result = empty($model::find([$field => $this->value]));
        $this->checkResult($result);
        return $this;
    }

    /**
     * 允许的ip
     * @param $rule
     *
     * @return $this
     */
    public function allowIp($rule) {
        $this->check_role = 'denyIp';
        $ip = request()->ip();
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        $result = in_array($ip, $rule);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 禁止的 ip
     * @param $rule
     *
     * @return $this
     */
    public function denyIp($rule) {
        $this->check_role = 'denyIp';
        $ip = request()->ip();
        if (!is_array($rule)) {
            $rule = explode(',', $rule);
        }
        $result = !in_array($ip, $rule);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证日期在_前
     * @access protected
     *
     * @param mixed $rule 验证规则
     *
     * @return $this
     */
    public function before($rule) {
        $this->check_role = 'before';
        $this->rule = $rule;
        $result = strtotime($this->value) <= strtotime($rule);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证日期在_后
     * @access protected
     *
     * @param mixed $rule 验证规则
     *
     * @return $this
     */
    public function after($rule) {
        $this->check_role = 'after';
        $this->rule = $rule;
        $result = strtotime($this->value) > strtotime($rule);
        $this->checkResult($result);
        return $this;
    }

    /**
     * 验证有效期
     * @access protected
     *
     * @param mixed $rule 验证规则
     *
     * @return $this
     */
    protected function expire($rule) {
        $this->check_role = 'expire';
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        $this->rule = implode(',', $rule);
        list($start, $end) = $rule;
        if (!is_numeric($start)) {
            $start = strtotime($start);
        }

        if (!is_numeric($end)) {
            $end = strtotime($end);
        }
        $time = time();
        $result = $time >= $start && $time <= $end;
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
    protected function checkRegex($value, $rule) {

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
     * @param array $vars
     *
     * @throws ValidateException
     */
    private function checkResult($result, $vars = []) {
        if (!$result) {
            $this->isValidate = false;
            $vars[ 'attribute' ] = $this->as_name;
            $vars[ '1' ] = $this->p1;
            $vars[ '2' ] = $this->p2;
            $vars[ 'rule' ] = $this->rule;
            if ($this->msg_check) {
                $msg = Lang::get('validate', $this->msg_check, $vars);
                if (!$msg) {
                    $msg = $this->msg_check;
                }
            } else {
                $msg = Lang::get('validate', $this->check_role, $vars);
            }
            if ($this->is_throw) {
                throw new ValidateException($msg, 100010, null);
            } else {
                $this->msg = $msg;
            }
        }
        $this->p1 = null;
        $this->p2 = null;
        $this->rule = null;
    }

    private function strToSize($str){
        if(strpos($str,'M')>0||strpos($str,'m')>0){
            return substr($str,0,strlen($str)-1)*1024*1024;
        }
        if(strpos($str,'k')>0||strpos($str,'k')>0){
            return substr($str,0,strlen($str)-1)*1024;
        }
        return $str*1024;
    }

}