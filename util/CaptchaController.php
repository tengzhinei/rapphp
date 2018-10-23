<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/10/12
 * Time: 下午5:26
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;


use rap\config\Config;

class CaptchaController {

    public function index($id = "") {
        $captcha = new Captcha((array)Config::get('captcha'));
        $captcha->send($id);
    }

}