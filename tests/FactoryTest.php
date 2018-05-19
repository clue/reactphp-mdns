<?php

use Clue\React\Mdns\Factory;

class FactoryTest extends TestCase
{
    public function testCreateResolver()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $factory = new Factory($loop);

        $resolver = $factory->createResolver();

        $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
    }
}
