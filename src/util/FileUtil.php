<?php
/**
 * User: jinghao@duohuo.net
 * Date: 18/10/26
 * Time: 下午12:43
 * Link:  http://magapp.cc
 * Copyright:南京灵衍信息科技有限公司
 */

namespace rap\util;

/**
 * 文件操作类
 */
class FileUtil {

    /**
     * 删除文件或文件夹
     *
     * @param $dirOrFile
     *
     * @return bool
     */
    static function delete($dirOrFile) {
        if (is_file($dirOrFile)) {
            @unlink($dirOrFile);
            return true;
        }
        if (!$handle = @opendir($dirOrFile)) {
            return false;
        }
        while (false !== ($file = readdir($handle))) {
            if ($file !== "." && $file !== "..") {       //排除当前目录与父级目录
                $file = $dirOrFile . '/' . $file;
                if (is_dir($file)) {
                    self::delete($file);
                } else {
                    @unlink($file);
                }
            }

        }
        @rmdir($dirOrFile);
        return true;
    }

    /**
     * 复制 文件和文件夹
     *
     * @param $source
     * @param $dest
     */
    static function copy($source, $dest) {
        if (is_file($source)) {
            mkdir(dirname($dest), 0777, true);
            copy($source, $dest);
            return;
        }
        if (!is_dir($source)) {
            return;
        }
        if (!file_exists($dest)) {
            mkdir($dest, 0777, true);
        }
        $handle = opendir($source);
        while (($item = readdir($handle)) !== false) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $_source = $source . '/' . $item;
            $_dest = $dest . '/' . $item;
            if (is_file($_source)) {
                copy($_source, $_dest);
            }
            if (is_dir($_source)) {
                self::copy($_source, $_dest);
            }
        }
        closedir($handle);
    }

    /**
     * 移动
     *
     * @param $source
     * @param $dest
     */
    static function move($source, $dest) {
        rename($source, $dest);
    }

    /**
     * 压缩
     *
     * @param string $path
     * @param string $zipPath
     */
    static function zip($path, $zipPath) {
        $zip = new \ZipArchive();
        unlink($zipPath);
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            self::addFileToZip($path, $zip); //调用方法，对要打包的根目录进行操作，并将ZipArchive的对象传递给方法
            $zip->close(); //关闭处理的zip文件
        }
    }

    /**
     * @param             $path
     * @param \ZipArchive $zip
     * @param string      $dir_path
     */
    private static function addFileToZip($path, \ZipArchive $zip, $dir_path = '') {
        if (!$dir_path) {
            $dir_path = $path;
        }
        $handler = opendir($path); //打开当前文件夹由$path指定。
        /*
        循环的读取文件夹下的所有文件和文件夹
        其中$filename = readdir($handler)是每次循环的时候将读取的文件名赋值给$filename，
        为了不陷于死循环，所以还要让$filename !== false。
        一定要用!==，因为如果某个文件名如果叫'0'，或者某些被系统认为是代表false，用!=就会停止循环
        */
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != ".." && $filename != ".DS_Store") {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
                if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归
                    self::addFileToZip($path . "/" . $filename, $zip, $dir_path);
                } else { //将文件加入zip对象
                    $to = str_replace($dir_path, '', $path) . "/" . $filename;
                    $zip->addFile($path . "/" . $filename, $to);
                }
            }
        }
        @closedir($path);
    }

    /**
     * 解压
     *
     * @param $path
     * @param $toPath
     */
    static function unzip($path, $toPath) {
        $zip = new \ZipArchive();
        $res = $zip->open($path);
        if ($res) {
            $zip->extractTo($toPath);
        }
        $zip->close();
    }


    /**
     * 迭代目录文件
     *
     * @param          $dir
     * @param \Closure $closure
     */
    static function each($dir, \Closure $closure) {
        $handler = opendir($dir);
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != ".." && $filename != ".DS_Store") {
                $closure($dir . "/" . $filename, $filename);
            }
        }
        @closedir($dir);
    }

    static function eachAll($dir, \Closure $closure) {
        $child_dirs = scandir($dir);
        foreach ($child_dirs as $child_dir) {
            if ($child_dir != '.' && $child_dir != '..' && $child_dir != '.DS_Store') {
                $files[] = $child_dir;
                $closure($dir . "/" . $child_dir, $child_dir);
            }
        }
    }

    /**
     * 读文件
     *
     * @param $filename
     *
     * @return string
     */
    static function readFile($filename) {
        //swoole 4.2后 file_get_contents已被协程化
        return file_get_contents($filename);
    }

    /**
     * 写文件
     * @param string $filename
     * @param string $fileContent
     * @param int    $flags
     */
    static function writeFile( $filename,  $fileContent,  $flags=null){
        mkdir(dirname($filename), 0777, true);
        file_put_contents($filename,$fileContent,$flags);
    }


}