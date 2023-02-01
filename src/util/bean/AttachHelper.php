<?php

namespace rap\util\bean;

use rap\config\Config;
use rap\ioc\IocInject;
use rap\storage\Storage;

class AttachHelper
{
    use IocInject;



    /**
     * @param $url
     *
     * @return string
     */
    public function urlFix($url)
    {
        return $url;
    }

    /**
     * @param $url
     * @return mixed
     */
    public function urlClear($url)
    {
        return $url;
    }

    /**
     * @param $url
     * @return bool
     */
    public function delete($url)
    {
        return true;
    }
}
