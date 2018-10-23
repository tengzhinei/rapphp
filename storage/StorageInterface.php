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
    const resize_rect_out= 1; //常量，标识缩略图等比例缩放类型
    const resize_rect_in=2;  //常量，标识缩略图缩放后填充类型
    const resize_fix_w=3;  //固定框
    const resize_fix_h=4;   //固定高
    const resize_fix=6; //常量，标识缩略图固定尺寸缩放类型
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
     * @param string $name 文件name
     * @return string
     */
    public function getUrl($name);

    public function getDomain();
    /**
     * 获取图片可访问地址
     * 如果是视频请返回视频的封面图片
     * @param string $name 文件name
     * @param int $width   宽
     * @param int $height  高
     * @param bool $water  是否水印
     * @param int $crop    裁剪方法
     * @param int $blur    模糊程度
     * @return string
     */
    public function getPicUrl($name,$width=0,$height=0,$water=false,$crop=self::resize_rect_in,$blur=-1);


    /**
     * 删除文件
     * @param $name
     */
    public function delete($name);

}