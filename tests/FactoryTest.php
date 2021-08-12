<?php

namespace Clue\Tests\React\Mdns;

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

    public function testConstructWithoutLoopAssignsLoopAutomatically()
    {
        $factory = new Factory();

        $ref = new \ReflectionProperty($factory, 'loop');
        $ref->setAccessible(true);
        $loop = $ref->getValue($factory);

        $this->assertInstanceOf('React\EventLoop\LoopInterface', $loop);
    }
}
