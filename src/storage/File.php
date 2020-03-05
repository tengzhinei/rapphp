<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 18/2/7
 * Time: 下午7:02
 */

namespace rap\storage;

class File
{

    public $name     = "";
    public $type     = "";
    public $size     = "";
    public $path_tmp = "";
    public $error    = "";
    public $ext      = "";

    public static function fromRequest($upload_file)
    {
        $file = new File();
        $file->name = $upload_file[ 'name' ];
        $file->type = $upload_file[ 'type' ];
        $file->size = $upload_file[ 'size' ];
        $file->path_tmp = $upload_file[ 'tmp_name' ];
        $file->error = $upload_file[ 'error' ];
        $x = explode(".", $file->name);
        $file->ext = $x[ count($x) - 1 ];
        return $file;
    }

    /**
     * 获取文件类型信息
     * @access public
     * @return string
     */
    public function getMime()
    {
        $f_info = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($f_info, $this->path_tmp);
    }

    /**
     * 检测是否合法的上传文件
     * @return bool
     */
    public function isValid()
    {
        return true;
    }
}
