<?php
namespace rap\storage;
use rap\exception\SystemException;
use rap\ioc\Ioc;

/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/9/4
 * Time: 上午11:03
 */
class Storage{

    /**
     * @var StorageInterface
     */
    private $storage;

    static private $storageArr =[];

    /**
     * Cache constructor.
     * @param StorageInterface $cache
     */
    private function __construct(StorageInterface $cache){
        $this->storage = $cache;
    }

    /**
     * 根据名称获取一个存储引擎
     * @param $name
     * @return StorageInterface
     * @throws SystemException
     */
    public static function getStorage($name=StorageInterface::class){
        if(static::$storageArr[$name]){
            return static::$storageArr[$name];
        }
        $storage=Ioc::get($name);
        if(!$storage){
            throw new SystemException($name."文件存储不存在,你写个类可以继承自".StorageInterface::class);
        }else
        if($storage instanceof StorageInterface){
            static::$storageArr[$name]=new Storage($storage);
            return static::$storageArr[$name];
        }else{
            throw new SystemException($name."文件存储需要继承".StorageInterface::class);
        }
    }

    /**
     * 上传文件
     * @param File $file 文件
     * @param string $category 文件类别
     * @param string $name 文件保存名称
     */
    public function upload(File $file,$category,$name=""){
      return  $this->storage->upload($file,$category,$name);
    }

    /**
     * 获取文件外部可访问地址,如http://pic.com/head/user_1.jpg
     * @param string $file_id 文件id
     * @return string
     */
    public function getUrl($file_id){
        return  $this->storage->getUrl($file_id);
    }

    /**
     * 获取图片可访问地址
     * 如果是视频请返回视频的封面图片
     * @param string $file_id 文件id
     * @param int $width
     * @param int $height
     * @param bool $water
     * @param int $crop
     * @return string
     */
    public function getPicUrl($file_id,$width=0,$height=0,$water=false,$crop=1){
        return  $this->storage->getPicUrl($file_id,$width,$height,$crop,$water);
    }

    /**
     * 删除文件
     * @param $file_id
     */
    public function delete($file_id){
        $this->storage->delete($file_id);
    }

}