<?php

namespace Clue\React\Mdns;

use React\Dns\BadServerException;
use React\Dns\Model\Message;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Clue\React\Multicast\Factory as DatagramFactory;
use React\Dns\Query\ExecutorInterface;
use React\Dns\Query\Query;
use React\Dns\Query\TimeoutException;

/**
 * DNS executor that uses multicast sockets
 *
 * Based on React\Dns\Query\Executor which should eventually be split into
 * multiple smaller components..
 */
class MulticastExecutor implements ExecutorInterface
{
    private $loop;
    private $parser;
    private $dumper;
    private $timeout;
    private $factory;

    public function __construct(LoopInterface $loop, Parser $parser = null, BinaryDumper $dumper = null, $timeout = 5, DatagramFactory $factory = null)
    {
        if ($parser === null) {
            $parser = new Parser();
        }
        if ($dumper === null) {
            $dumper = new BinaryDumper();
        }
        if ($factory === null) {
            $factory = new DatagramFactory($loop);
        }

        $this->loop = $loop;
        $this->parser = $parser;
        $this->dumper = $dumper;
        $this->timeout = $timeout;
        $this->factory = $factory;
    }

    public function query($nameserver, Query $query)
    {
        $request = $this->prepareRequest($query);

        $queryData = $this->dumper->toBinary($request);

        return $this->doQuery($nameserver, $queryData, $query->name);
    }

    public function prepareRequest(Query $query)
    {
        $request = new Message();
        $request->header->set('id', $this->generateId());
        $request->header->set('rd', 1);
        $request->questions[] = (array) $query;
        $request->prepare();

        return $request;
    }

    public function doQuery($nameserver, $queryData, $name)
    {
        $that = $this;
        $parser = $this->parser;
        $loop = $this->loop;

        $deferred = new Deferred(function ($_, $reject) use (&$conn, &$timer, $name) {
            $conn->close();
            $timer->cancel();

            $reject(new \RuntimeException(sprintf("DNS query for %s cancelled", $name)));
        });

        $timer = $this->loop->addTimer($this->timeout, function () use (&$conn, $name, $deferred) {
            $conn->close();
            $deferred->reject(new TimeoutException(sprintf("DNS query for %s timed out", $name)));
        });

        $conn = $this->factory->createSender();

        $conn->on('message', function ($data) use ($conn, $parser, $deferred, $timer) {
            $response = new Message();
            $responseReady = $parser->parseChunk($data, $response);

            $conn->close();
            $timer->cancel();

            if (!$responseReady) {
                $deferred->reject(new BadServerException('Invalid response received'));

                return;
            }

            if ($response->header->isTruncated()) {
                $deferred->reject(new BadServerException('The server set the truncated bit although we issued a TCP request'));

                return;
            }

            $deferred->resolve($response);
        });

        $conn->send($queryData, $nameserver);

        return $deferred->promise();
    }

    protected function generateId()
    {
        return mt_rand(0, 0xffff);
    }
}
