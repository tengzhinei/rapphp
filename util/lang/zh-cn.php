<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/9/9
 * Time: 上午10:50
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

return [
  'validate'=>[
      'require'     => ':attribute require',
      'must'        => ':attribute must',
      'number'      => ':attribute must be numeric',
      'integer'     => ':attribute must be integer',
      'float'       => ':attribute must be float',
      'boolean'     => ':attribute must be bool',
      'email'       => ':attribute not a valid email address',
      'mobile'      => ':attribute not a valid mobile',
      'array'       => ':attribute must be a array',
      'accepted'    => ':attribute must be yes,on or 1',
      'date'        => ':attribute not a valid datetime',
      'file'        => ':attribute not a valid file',
      'image'       => ':attribute not a valid image',
      'alpha'       => ':attribute must be alpha',
      'alphaNum'    => ':attribute must be alpha-numeric',
      'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
      'activeUrl'   => ':attribute not a valid domain or ip',
      'chs'         => ':attribute must be chinese',
      'chsAlpha'    => ':attribute must be chinese or alpha',
      'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
      'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
      'url'         => ':attribute not a valid url',
      'ip'          => ':attribute not a valid ip',
      'dateFormat'  => ':attribute must be dateFormat of :rule',
      'in'          => ':attribute must be in :rule',
      'notIn'       => ':attribute be notin :rule',
      'between'     => ':attribute must between :1 - :2',
      'notBetween'  => ':attribute not between :1 - :2',
      'length'      => 'size of :attribute must be :rule',
      'max'         => 'max size of :attribute must be :rule',
      'min'         => 'min size of :attribute must be :rule',
      'after'       => ':attribute cannot be less than :rule',
      'before'      => ':attribute cannot exceed :rule',
      'expire'      => ':attribute not within :rule',
      'allowIp'     => 'access IP is not allowed',
      'denyIp'      => 'access IP denied',
      'confirm'     => ':attribute out of accord with :2',
      'different'   => ':attribute cannot be same with :2',
      'egt'         => ':attribute must greater than or equal :rule',
      'gt'          => ':attribute must greater than :rule',
      'elt'         => ':attribute must less than or equal :rule',
      'lt'          => ':attribute must less than :rule',
      'eq'          => ':attribute must equal :rule',
      'unique'      => ':attribute has exists',
      'regex'       => ':attribute not conform to the rules',
      'method'      => 'invalid Request method',
      'token'       => 'invalid token',
      'fileSize'    => 'filesize not match',
      'fileExt'     => 'extensions to upload is not allowed',
      'fileMime'    => 'mimetype to upload is not allowed',
  ]
];
