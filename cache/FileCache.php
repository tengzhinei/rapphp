<?php
/**
 * 南京灵衍信息科技有限公司
 * User: jinghao@duohuo.net
 * Date: 17/10/18
 * Time: 下午5:33
 */

namespace rap\cache;

/**
 * 文件缓存
 * Class FileCache
 * @package rap\cache
 */
class FileCache implements CacheInterface {
    private $options = ['path' => "cache/",
                        'data_compress' => false];

    public function config($options = []) {
        if (!empty($options)) {
            $this->options['path']=RUNTIME."cache".DS;
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * 取得变量的存储文件名
     *
     * @param        $name
     * @param string $dirName
     *
     * @return string
     */
    protected function getCacheFile($name, $dirName = "") {
        if ($dirName) {
            $dirName .= DIRECTORY_SEPARATOR;
        }
        $name = md5($name . '12' . $name);
        $filename = $this->options[ 'path' ] . $dirName . $name . '.php';
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $filename;
    }

    /**
     * 设置缓存
     *
     * @param $name
     * @param $value
     * @param $expire
     *
     * @return bool
     */
    public function set($name, $value, $expire) {
        if (is_null($expire)) {
            $expire = $this->options[ 'expire' ];
        }
        $filename = $this->getCacheFile($name);

        $data = serialize($value);
        if ($this->options[ 'data_compress' ] && function_exists('gzcompress')) {
            //数据压缩
            $data = gzcompress($data, 3);
        }
        $data = "<?php\n//" . sprintf('%012d', $expire) . $data . "\n?>";
        $result = file_put_contents($filename, $data);
        if ($result) {
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }


    public function get($name, $default = null) {
        $filename = $this->getCacheFile($name);
        if (!is_file($filename)) {
            return $default;
        }
        $content = file_get_contents($filename);
        if (false !== $content) {
            $expire = (int)substr($content, 8, 12);
            if (0 != $expire && $_SERVER[ 'REQUEST_TIME' ] > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                $this->unlink($filename);
                return $default;
            }
            $content = substr($content, 20, -3);
            if ($this->options[ 'data_compress' ] && function_exists('gzcompress')) {
                //启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        } else {
            return $default;
        }

    }

    public function has($name) {
        return $this->get($name) ? true : false;
    }

    public function inc($name, $step = 1) {
        if ($this->has($name)) {
            $value = $this->get($name) + $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    public function dec($name, $step = 1) {
        if ($this->has($name)) {
            $value = $this->get($name) - $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    public function remove($name) {
        return $this->unlink($this->getCacheFile($name));
    }

    public function clear() {
        $files = (array)glob($this->options[ 'path' ] . ($this->options[ 'prefix' ] ? $this->options[ 'prefix' ] . DIRECTORY_SEPARATOR : '') . '*');
        foreach ($files as $path) {
            if (is_dir($path)) {
                array_map('unlink', glob($path . '/*.php'));
            } else {
                unlink($path);
            }
        }
    }

    public function hashSet($name, $key, $value) {
        $filename = $this->getCacheFile($key, $name);
        $data = serialize($value);
        if ($this->options[ 'data_compress' ] && function_exists('gzcompress')) {
            //数据压缩
            $data = gzcompress($data, 3);
        }
        $data = "<?php\n//" . sprintf('%012d', 0) . $data . "\n?>";
        $result = file_put_contents($filename, $data);
        if ($result) {
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }

    public function hashGet($name, $key, $default) {
        $filename = $this->getCacheFile($key, $name);
        if (!is_file($filename)) {
            return $default;
        }
        $content = file_get_contents($filename);
        if (false !== $content) {
            $expire = (int)substr($content, 8, 12);
            if (0 != $expire && $_SERVER[ 'REQUEST_TIME' ] > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                $this->unlink($filename);
                return $default;
            }
            $content = substr($content, 20, -3);
            if ($this->options[ 'data_compress' ] && function_exists('gzcompress')) {
                //启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        } else {
            return $default;
        }

    }

    public function hashRemove($name, $key) {
        $filename = $this->getCacheFile($key, $name);
        $this->unlink($filename);
    }

    /**
     * 判断文件是否存在后，删除
     *
     * @param $path
     *
     * @return bool
     * @author byron sampson <xiaobo.sun@qq.com>
     * @return boolean
     */
    private function unlink($path) {
        return is_file($path) && unlink($path);
    }
}