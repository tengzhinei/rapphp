<?php


namespace rap\db;

use rap\config\Config;
use rap\ioc\IocInject;
use rap\storage\Storage;

class RecordAttachHelper {
    use IocInject;

    private $defaultDomain;

    /**
     * XiciRecordAttachHelper __construct.
     */
    public function __construct()
    {
        $this->defaultDomain = Config::getFileConfig()['storage']['cname'];
    }

    /**
     * @param $url
     *
     * @return string
     */
    public function domainFix($url) {
        if (!(strpos($url, 'http') === 0) && $url) {
            return $this->defaultDomain . $url;
        }
        return $url;
    }

    public function domainClear($url) {
        if($this->defaultDomain){
            $url = str_replace($this->defaultDomain, "", $url);
        }
        return $url;
    }

    public function delete($url){
        Storage::getStorage()->delete($url);
    }

}