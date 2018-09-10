<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/9/9
 * Time: 下午4:35
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\web\validate;


class Validate {

    public function validate(){
        validateParam("name")->dateFormat("","");
        validateParam("age")->between(12,32);


    }

}