<?php

namespace Clue\React\Mdns;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Dns\Query\ExecutorInterface;
use React\Dns\Resolver\Resolver;

class Factory
{
    const DNS = '224.0.0.251:5353';

    /** @var LoopInterface */
    private $loop;

    /** @var ExecutorInterface */
    private $executor;

    /**
     * @param ?LoopInterface $loop
     * @param ?ExecutorInterface $executor
     */
    public function __construct(LoopInterface $loop = null, ExecutorInterface $executor = null)
    {
        $this->loop = $loop ?: Loop::get();
        $this->executor = $executor ?: new MulticastExecutor($loop);
    }

    public function createResolver()
    {
        return new Resolver(self::DNS, $this->executor);
    }
}
