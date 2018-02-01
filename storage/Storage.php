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
    public function getStorage($name){
        if(static::$storageArr[$name]){
            return static::$storageArr[$name];
        }
        $cache=Ioc::get($name);
        if(!$cache){
            throw new SystemException($name."文件存储不存在,你写个类可以继承自".StorageInterface::class);
        }else
        if($cache instanceof StorageInterface){
            static::$storageArr[$name]=new Storage($cache);
            return static::$storageArr[$name];
        }else{
            throw new SystemException($name."文件存储需要继承".StorageInterface::class);
        }
    }

    /**
     * 上传文件
     * @param string $file 文件地址
     * @param string $category 文件类别
     * @param string $name 文件保存名称
     */
    public function upload($file,$category,$name=""){
        $this->storage->upload($file,$category,$name);
    }

    /**
     * 获取文件外部可访问地址,如http://pic.com/head/user_1.jpg
     * @param string $category 文件类别
     * @param string $name 名称
     * @return string
     */
    public function getUrl($category,$name){
        return  $this->storage->getUrl($category,$name);
    }

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
    public function getPicUrl($category,$name,$width=0,$height=0,$crop=-1){
        return  $this->storage->getPicUrl($category,$name,$width,$height,$crop);
    }

    /**
     * 查看文件是否存在
     * @param $category
     * @param $name
     * @return bool
     */
    public function has($category,$name){
        return $this->storage->has($category,$name);
    }

    /**
     * 删除文件
     * @param $category
     * @param $name
     */
    public function delete($category,$name){
        $this->storage->delete($category,$name);
    }

}