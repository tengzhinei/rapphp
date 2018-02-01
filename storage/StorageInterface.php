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

    const crop_fitXY=1;//固定宽高,图片才切后不超过设定的宽高
    const crop_fitCenter=2;//图片宽高不超过指定值,居中裁切
    const crop_fitTop=3;//固定宽,居距顶裁切
    const crop_fitX=3;//固定宽,居中裁切
    const crop_fitY=4;//固定高,居中裁切

    /**
     * 上传文件
     * @param string $file 文件地址
     * @param string $category 文件类别
     * @param string $name 文件保存名称
     */
    public function upload($file,$category,$name="");

    /**
     * 获取文件外部可访问地址,如http://pic.com/head/user_1.jpg
     * @param string $category 文件类别
     * @param string $name 名称
     * @return string
     */
    public function getUrl($category,$name);

    /**
     * 获取图片可访问地址
     * 如果是视频请返回视频的封面图片
     * @param string $category 文件类别
     * @param string $name 名称
     * @param int $width
     * @param int $height
     * @param int $crop
     * @return string
     */
    public function getPicUrl($category,$name,$width=0,$height=0,$crop=-1);


    /**
     * 查看文件是否存在
     * @param $category
     * @param $name
     * @return bool
     */
    public function has($category,$name);

    /**
     * 删除文件
     * @param $category
     * @param $name
     */
    public function delete($category,$name);

}