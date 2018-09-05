<?php
namespace rap\util;

use Endroid\QrCode\QrCode;

/**
 * User: jinghao@duohuo.net
 * Date: 18/9/5
 * Time: 下午2:17
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */
class Utils {

    /**
     * 获取二维码
     *
     * @param        $url
     *
     * @return string
     */
    static function getQrcode($url) {
        $qrCode = new QrCode();
        $qrCode->setText($url)
               ->setSize(300)
               ->setPadding(10)
               ->setErrorCorrection('low')
               ->setForegroundColor(['r' => 0,
                                     'g' => 0,
                                     'b' => 0,
                                     'a' => 0])
               ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
               ->setLabel('')
               ->setLabelFontSize(16)
               ->setImageType(QrCode::IMAGE_TYPE_PNG);
        mkdir(ROOT_PATH . '/runtime/temp/qrcode/', 0777, true);
        $filename = '/runtime/temp/' . md5($url) . '.png';
        $qrCode->save(ROOT_PATH . $filename);
        return ROOT_PATH . $filename;
    }

}