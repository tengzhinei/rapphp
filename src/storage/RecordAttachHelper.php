<?php


namespace rap\storage;

use rap\ioc\IocInject;

class RecordAttachHelper {
    use IocInject;

    /**
     * @param $url
     *
     * @return string
     */
    public function domainFix($url) {
        if (!(strpos($url, 'http') === 0) && $url) {
            $domain = Storage::getStorage()->getDomain();
            return $domain . $url;
        }
        return $url;
    }

    public function domainClear($url) {
        $domain = Storage::getStorage()->getDomain();
        if ($domain) {
            $url = str_replace($domain, "", $url);
        }
        return $url;
    }

    public function delete($url){
        Storage::getStorage()->delete($url);
    }


}