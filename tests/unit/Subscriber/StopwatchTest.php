<?php

namespace Campru\GuzzleBundle\Subscriber;

use GuzzleHttp\Client;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Transaction;

/**
 * Stopwatch unit test.
 *
 * @aauthor David Camprubí <david.camprubi@gmail.com>
 */
class StopwatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test event emitted when request transaction has ended.
     */
    public function testEndEvent()
    {
        $request  = new Request('GET', '/');
        $response = new Response(200);

        $transaction = new Transaction(new Client(), $request);
        $transaction->response     = $response;
        $transaction->transferInfo = [
            'total_time'   => 10,
            'connect_time' => 1,
        ];

        $event = new EndEvent($transaction);

        $stopwath = new Stopwatch(new \SplObjectStorage);
        $stopwath->onEnd($event);

        $expectedResult = [
            'total'      => 10,
            'connection' => 1,
        ];

        $storage = $stopwath->getStorage();

        $this->assertInstanceOf('SplObjectStorage', $storage);
        $this->assertSame($expectedResult, $storage->offsetGet($response));
    }
}