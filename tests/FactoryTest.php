<?php

use Clue\React\Mdns\Factory;

class FactoryTest extends TestCase
{
    public function testCreateResolver()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $factory = new Factory($loop);

        $resolver = $factory->createResolver();

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
    }
}
