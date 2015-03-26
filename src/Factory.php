<?php

namespace Clue\React\Mdns;

use React\EventLoop\LoopInterface;
use React\Dns\Query\ExecutorInterface;
use React\Dns\Resolver\Resolver;

class Factory
{
    const DNS = '224.0.0.251:5353';

    private $loop;
    private $executor;

    public function __construct(LoopInterface $loop, ExecutorInterface $executor = null)
    {
        if ($executor === null) {
            $executor = new MulticastExecutor($loop);
        }

        $this->loop = $loop;
        $this->executor = $executor;
    }

    public function createResolver()
    {
        return new Resolver(self::DNS, $this->executor);
    }
}
