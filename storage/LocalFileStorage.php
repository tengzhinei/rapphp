<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/2/7
 * Time: 下午7:01
 */

namespace rap\storage;


use rap\exception\FileUploadException;

class LocalFileStorage implements StorageInterface{

    private $config=[
        'base_path'=>'/public/upload/file',
        'water'=>"/public/water.png",
        'cdn'=>""
    ];

    public function config($config){
        $this->config=array_merge($this->config,$config);
    }

    /**
     * 文件上传
     * @param File $file
     * @param string $category
     * @param string $name
     * @param bool $replace
     * @return bool
     * @throws FileUploadException
     */
    public function upload(File $file, $category, $name = "",$replace= false){
        $path=$this->config['base_path'].DIRECTORY_SEPARATOR;
        // 文件上传失败，捕获错误代码
        if (!empty($file->error)) {
            return false;
        }
        // 检测合法性
        if (!$file->isValid()) {
            throw new FileUploadException('非法上传文件');
        }
        if(!$name){
            $size='';
            if(in_array($file->ext,['jpg','png','jpeg'])){
                $img_info = getimagesize($file->path_tmp);
                $size="_".$img_info[0]."_".$img_info[1];
            }
            $name=md5_file($file->path_tmp).$size.".".$file->ext;
        }
        $file_id=$category.DIRECTORY_SEPARATOR.date("Ymd").DIRECTORY_SEPARATOR . $name;
        // 文件保存命名规则
        $filename = ROOT_PATH.$path.$file_id;
        // 检测目录
        if (false === $this->checkPath(dirname($filename))) {
            throw new FileUploadException("目录创建失败");
        }
        /* 不覆盖同名文件 */
        if (!$replace && is_file($filename)) {
            return $file_id;
        }
        if (!move_uploaded_file($file->path_tmp, $filename)) {
            throw new FileUploadException('文件上传保存错误！');
        }
        return $file_id;
    }

    /**
     * 获取原图
     * @param string $file_id
     * @return string
     */
    public function getUrl($file_id){
        $path=$this->config['base_path'].DIRECTORY_SEPARATOR;
        return $this->config['cdn'].$path.$file_id;
    }

    /**
     * 获取图片缩略图
     * @param string $file_id
     * @param int $width
     * @param int $height
     * @param int $crop
     * @param bool $water
     * @param int $blur
     * @return string
     */
    public function getPicUrl($file_id, $width = 0, $height = 0,$water=false, $crop = self::resize_rect_in,$blur=-1){
        $path=$this->config['base_path'].DIRECTORY_SEPARATOR.$file_id;
        $image=\think\Image::open(ROOT_PATH.$path);
        $p=explode(DIRECTORY_SEPARATOR,$file_id);
        $name=array_pop($p);
        $name_ext=explode(".",$name);
        $save_name=$this->config['base_path'].DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$p).DIRECTORY_SEPARATOR
            .$name_ext[0].DIRECTORY_SEPARATOR;
        mkdir(ROOT_PATH.$save_name);
        $save_name.=$width."_".$height."_".$crop."_".($water?1:"").".".$name_ext[1];
        $image->thumb($width,$height,$crop);
        if($water&&is_file(ROOT_PATH.$this->config['water'])){
            $image->water(ROOT_PATH.$this->config['water']);
        }
        $image ->save(ROOT_PATH.$save_name);
        return $this->config['cdn'].$save_name;
    }


    /**
     * 删除文件
     * @param $file_id
     */
    public function delete($file_id){
        $filename=$this->config['base_path'].DIRECTORY_SEPARATOR.$file_id;
        unlink(ROOT_PATH.$filename);
        $p=explode(DIRECTORY_SEPARATOR,$file_id);
        $name=array_pop($p);
        $name_ext=explode(".",$name);
        $save_name=ROOT_PATH.$this->config['base_path'].DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$p).DIRECTORY_SEPARATOR
            .$name_ext[0].DIRECTORY_SEPARATOR;
        $files = (array) glob($save_name . '*');
        var_dump($save_name);
        foreach ($files as $path) {
            if (is_dir($path)) {
                array_map('unlink', glob($path . '/*.php'));
            } else {
                unlink($path);
            }
        }
        rmdir($save_name);
    }

    /**
     * 检查目录是否可写
     * @param  string   $path    目录
     * @return boolean
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }
        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            return false;
        }
    }

    public function getDomain(){
        return $this->config['cdn'];
    }

}