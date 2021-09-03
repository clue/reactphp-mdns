<?php

namespace Clue\React\Mdns;

use React\Dns\BadServerException;
use React\Dns\Model\Message;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\Loop;
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
    /** @var LoopInterface */
    private $loop;

    /** @var Parser */
    private $parser;

    /** @var BinaryDumper */
    private $dumper;

    /** @var number */
    private $timeout;

    /** @var DatagramFactory */
    private $factory;

    /**
     * @param ?LoopInterface $loop
     * @param ?Parser $parser
     * @param ?BinaryDumper $dumper
     * @param number $timeout
     * @param ?DatagramFactory $factory
     */
    public function __construct(LoopInterface $loop = null, Parser $parser = null, BinaryDumper $dumper = null, $timeout = 5, DatagramFactory $factory = null)
    {
        $this->loop = $loop ?: Loop::get();
        $this->parser = $parser ?: new Parser();
        $this->dumper = $dumper ?: new BinaryDumper();
        $this->timeout = $timeout;
        $this->factory = $factory ?: new DatagramFactory($this->loop);
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

        $deferred = new Deferred(function ($_, $reject) use (&$conn, &$timer, $loop, $name) {
            $conn->close();
            $loop->cancelTimer($timer);

            $reject(new \RuntimeException(sprintf("DNS query for %s cancelled", $name)));
        });

        $timer = $this->loop->addTimer($this->timeout, function () use (&$conn, $name, $deferred) {
            $conn->close();
            $deferred->reject(new TimeoutException(sprintf("DNS query for %s timed out", $name)));
        });

        $conn = $this->factory->createSender();

        $conn->on('message', function ($data) use ($conn, $parser, $deferred, $timer, $loop) {
            $response = new Message();
            $responseReady = $parser->parseChunk($data, $response);

            $conn->close();
            $loop->cancelTimer($timer);

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
