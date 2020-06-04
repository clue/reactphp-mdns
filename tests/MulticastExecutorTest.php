<?php

namespace Clue\Tests\React\Mdns;

use Clue\React\Mdns\MulticastExecutor;
use Clue\React\Mdns\Factory;
use React\Dns\Query\Query;

class MulticastExecutorTest extends TestCase
{
    public function testQueryWillReturnPromise()
    {
        $nameserver = Factory::DNS;
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $parser = $this->getMockBuilder('React\Dns\Protocol\Parser')->getMock();
        $dumper = $this->getMockBuilder('React\Dns\Protocol\BinaryDumper')->getMock();
        $sockets = $this->getMockBuilder('Clue\React\Multicast\Factory')->disableOriginalConstructor()->getMock();

        $executor = new MulticastExecutor($loop, $parser, $dumper, 5, $sockets);

        $socket = $this->getMockBuilder('React\Datagram\SocketInterface')->getMock();

        $dumper->expects($this->once())->method('toBinary')->will($this->returnValue('message'));
        $sockets->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        $socket->expects($this->once())->method('send')->with($this->equalTo('message'), $this->equalTo($nameserver));

        $query = new Query('name', 'type', 'class', time());

        $ret = $executor->query($nameserver, $query);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $ret);
    }

    public function testCancellingPromiseWillCloseSocketAndReject()
    {
        $nameserver = Factory::DNS;
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $parser = $this->getMockBuilder('React\Dns\Protocol\Parser')->getMock();
        $dumper = $this->getMockBuilder('React\Dns\Protocol\BinaryDumper')->getMock();
        $sockets = $this->getMockBuilder('Clue\React\Multicast\Factory')->disableOriginalConstructor()->getMock();

        $executor = new MulticastExecutor($loop, $parser, $dumper, 5, $sockets);

        $socket = $this->getMockBuilder('React\Datagram\SocketInterface')->getMock();
        $socket->expects($this->once())->method('close');
        $socket->expects($this->once())->method('send')->with($this->equalTo('message'), $this->equalTo($nameserver));
        $sockets->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        // prefer newer EventLoop 1.0/0.5+ TimerInterface or fall back to legacy namespace
        $timer = $this->getMockBuilder(
            interface_exists('React\EventLoop\TimerInterface') ? 'React\EventLoop\TimerInterface' : 'React\EventLoop\Timer\TimerInterface'
        )->getMock();

        $loop->expects($this->once())->method('addTimer')->willReturn($timer);
        $loop->expects($this->once())->method('cancelTimer')->with($timer);

        $dumper->expects($this->once())->method('toBinary')->will($this->returnValue('message'));

        $query = new Query('name', 'type', 'class', time());

        $ret = $executor->query($nameserver, $query);
        $this->assertInstanceOf('React\Promise\CancellablePromiseInterface', $ret);

        $ret->cancel();

        $ret->then($this->expectCallableNever(), $this->expectCallableOnceWith($this->isInstanceOf('RuntimeException')));
    }
}
