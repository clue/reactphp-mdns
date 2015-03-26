<?php

use Clue\React\Mdns\MulticastExecutor;
use Clue\React\Mdns\Factory;
use React\Dns\Query\Query;
class MulticastExecutorTest extends TestCase
{
    public function testA()
    {
        $nameserver = Factory::DNS;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $parser = $this->getMock('React\Dns\Protocol\Parser');
        $dumper = $this->getMock('React\Dns\Protocol\BinaryDumper');
        $sockets = $this->getMockBuilder('Clue\React\Multicast\Factory')->disableOriginalConstructor()->getMock();

        $executor = new MulticastExecutor($loop, $parser, $dumper, 5, $sockets);

        $socket = $this->getMock('React\Datagram\SocketInterface');

        $dumper->expects($this->once())->method('toBinary')->will($this->returnValue('message'));
        $sockets->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        $socket->expects($this->once())->method('send')->with($this->equalTo('message'), $this->equalTo($nameserver));

        $query = new Query('name', 'type', 'class', time());

        $ret = $executor->query($nameserver, $query);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $ret);
    }
}
