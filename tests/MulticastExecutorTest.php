<?php

use Clue\React\Mdns\MulticastExecutor;
use Clue\React\Mdns\Factory;
use React\Dns\Query\Query;

class MulticastExecutorTest extends TestCase
{
    private $nameserver;
    private $loop;
    private $parser;
    private $dumper;
    private $sockets;

    public function setUp()
    {
        $this->nameserver = Factory::DNS;
        $this->loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $this->parser = $this->getMockBuilder('React\Dns\Protocol\Parser')->getMock();
        $this->dumper = $this->getMockBuilder('React\Dns\Protocol\BinaryDumper')->getMock();
        $this->sockets = $this->getMockBuilder('Clue\React\Multicast\Factory')->disableOriginalConstructor()->getMock();
    }

    public function testQueryWillReturnPromise()
    {
        $executor = new MulticastExecutor($this->loop, $this->parser, $this->dumper, 5, $this->sockets);

        $socket = $this->getMockBuilder('React\Datagram\SocketInterface')->getMock();

        $this->dumper->expects($this->once())->method('toBinary')->will($this->returnValue('message'));
        $this->sockets->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        $socket->expects($this->once())->method('send')->with($this->equalTo('message'), $this->equalTo($this->nameserver));

        $query = new Query('name', 'type', 'class', time());

        $ret = $executor->query($this->nameserver, $query);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $ret);
    }

    public function testCancellingPromiseWillCloseSocketAndReject()
    {
        $executor = new MulticastExecutor($this->loop, $this->parser, $this->dumper, 5, $this->sockets);

        $socket = $this->getMockBuilder('React\Datagram\SocketInterface')->getMock();
        $socket->expects($this->once())->method('close');
        $socket->expects($this->once())->method('send')->with($this->equalTo('message'), $this->equalTo($this->nameserver));
        $this->sockets->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        // prefer newer EventLoop 1.0/0.5+ TimerInterface or fall back to legacy namespace
        $timer = $this->getMockBuilder(
            interface_exists('React\EventLoop\TimerInterface') ? 'React\EventLoop\TimerInterface' : 'React\EventLoop\Timer\TimerInterface'
        )->getMock();

        $this->loop->expects($this->once())->method('addTimer')->willReturn($timer);
        $this->loop->expects($this->once())->method('cancelTimer')->with($timer);

        $this->dumper->expects($this->once())->method('toBinary')->will($this->returnValue('message'));

        $query = new Query('name', 'type', 'class', time());

        $ret = $executor->query($this->nameserver, $query);
        $this->assertInstanceOf('React\Promise\CancellablePromiseInterface', $ret);

        $ret->cancel();

        $ret->then($this->expectCallableNever(), $this->expectCallableOnceWith($this->isInstanceOf('RuntimeException')));
    }
}
