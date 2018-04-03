<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/6
 * Time: 下午5:22
 */

namespace rap\storage;

/**
 * 文件存储接口
 * Interface StorageInterface
 * @package rap\storage
 */
interface StorageInterface{

    const resize_fix_w=1;
    const resize_fix_h=2;
    const resize_rect_in=3;
    const resize_rect_out=4;
    const resize_fix=5;
    /**
     * 上传文件
     * @param File $file 文件地址
     * @param string $category 文件类别
     * @param string $name 文件保存名称
     * @param bool $replace 文件保存名称
     */
    public function upload(File $file,$category,$name="",$replace= false);

    /**
     * 获取文件外部可访问地址,如http://pic.com/head/user_1.jpg
     * @param string $file_id 文件id
     * @return string
     */
    public function getUrl($file_id);

    public function getDomain();
    /**
     * 获取图片可访问地址
     * 如果是视频请返回视频的封面图片
     * @param string $file_id 文件id
     * @param int $width
     * @param int $height
     * @param bool $water
     * @param int $crop
     * @param int $blur
     * @return string
     */
    public function getPicUrl($file_id,$width=0,$height=0,$water=false,$crop=self::resize_rect_in,$blur=-1);


    /**
     * 删除文件
     * @param $file_id
     */
    public function delete($file_id);

}