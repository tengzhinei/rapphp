<?php
namespace rap\swoole\coroutine;

use Swoole\Coroutine\Channel;
use \BadMethodCallException;
use \InvalidArgumentException;

class WaitGroup
{

    /**
     * @var Channel
     */
    protected $chan;

    protected $count = 0;

    protected $waiting = false;

    public function __construct()
    {
        $this->chan = new Channel(1);
    }

    public function add(int $delta = 1)
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: add called concurrently with wait');
        }
        $count = $this->count + $delta;
        if ($count < 0) {
            throw new InvalidArgumentException('WaitGroup misuse: negative counter');
        }
        $this->count = $count;
    }

    public function done()
    {
        $count = $this->count - 1;
        if ($count < 0) {
            throw new BadMethodCallException('WaitGroup misuse: negative counter');
        }
        $this->count = $count;
        if ($count === 0 && $this->waiting) {
            $this->chan->push(true);
        }
    }

    public function wait(float $timeout = -1)
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: reused before previous wait has returned');
        }
        if ($this->count > 0) {
            $this->waiting = true;
            $done = $this->chan->pop($timeout);
            $this->waiting = false;
            return $done;
        }
        return true;
    }
}