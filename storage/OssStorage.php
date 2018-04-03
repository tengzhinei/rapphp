<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/3/25
 * Time: 下午2:54
 */

namespace rap\storage;


use OSS\Core\OssException;
use OSS\OssClient;
use rap\config\Config;
use rap\helper\image\Image;

class OssStorage implements StorageInterface{

    private $config=[
        'accessKeyId' => "",
        'accessKeySecret' => "",
        'endpoint' => "http://oss-cn-shanghai.aliyuncs.com",
        'category'=>'',
        'bucket'=>'',
        'cname'=>'',
        'webp'=>false
    ];
    public function config($config){
        $this->config=array_merge($this->config,$config);
    }

    public function upload(File $file, $category, $name = "", $replace = false){
        $bucket=$this->config['bucket'];
        $ossClient = new OssClient($this->config['accessKeyId'], $this->config['accessKeySecret'], $this->config['endpoint']);
        $ossClient->setTimeout(60);
        $ossClient->setConnectTimeout(10);
        if(!$name){
            $size='';
            if(in_array($file->ext,['jpg','png','jpeg'])){
                $img_info = getimagesize($file->path_tmp);
                $size="_".$img_info[0]."_".$img_info[1];
            }
            $name=md5_file($file->path_tmp).$size.".".$file->ext;
        }
        $file_id=$this->config['category'].$category.DIRECTORY_SEPARATOR.date("Ymd").DIRECTORY_SEPARATOR . $name;
        try{
            $ossClient->uploadFile($bucket, $file_id, $file->path_tmp);
        }catch (OssException $exception){
            if($exception->getMessage()=='object name is empty'){
                $ossClient->createBucket($bucket);
                $ossClient->putBucketAcl($bucket,OssClient::OSS_ACL_TYPE_PUBLIC_READ);
                $ossClient->uploadFile($bucket, $file_id, $file->path_tmp);
            }
        }
        return $file_id;
    }

    public function getUrl($file_id){
        return   $this->config['cname'].$file_id;
    }

    public function getPicUrl($file_id, $width = 0, $height = 0, $water = false, $crop = self::resize_rect_in,$blur=-1){
       $url=$this->getUrl($file_id).'?x-oss-process=image';
        if($crop==1&&$width>0){//按宽 fix_w
            $url.="/resize,w_$width";
        }else if($crop==2&&$height>0){//按高 fix_h
            $url.="/resize,h_$height";
        }else if($width>0&&$height>0){
            if($crop==3){//限定在矩形框内,按长边优先.
                $url.="/resize,m_lfit,h_$height,w_$width";
            }else if($crop==4){//限定在矩形框外,按短边优先。
                $url.="/resize,m_mfit,h_$height,w_$width";
            }else if($crop==5){//固定宽高，自动裁剪
                $url.="/resize,m_fill,h_$height,w_$width";
            }
        }
        if($this->config['webp']){
            $url.='/format,webp';
        }
        if($blur>0){
            $url.="/blur,r_$blur,s_3";
        }
        if($water){
            $watermark=  Config::get("watermark");
            if($watermark){
                $url.="/watermark,image_$watermark,t_70,g_se,x_5,y_5";
            }
        }
        return $url;
    }

    public function delete($file_id){
        $bucket=$this->config['bucket'];
        $ossClient = new OssClient($this->config['accessKeyId'], $this->config['accessKeySecret'], $this->config['endpoint']);
        $ossClient->setTimeout(60);
        $ossClient->setConnectTimeout(10);
        $ossClient->deleteObject($bucket, $file_id);
    }

    public function getDomain(){
        return  $this->config['cname'];
    }


}