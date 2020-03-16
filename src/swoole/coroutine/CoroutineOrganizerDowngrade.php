<?php


namespace rap\swoole\coroutine;


class CoroutineOrganizerDowngrade implements ICoroutineOrganizer {

    public function goWithContext(\Closure $closure) {
        $closure();
    }

    public function go(\Closure $closure) {
        $closure();
    }

    public function group() {
       return $this;
    }

    public function wait() {
        return $this;
    }


}