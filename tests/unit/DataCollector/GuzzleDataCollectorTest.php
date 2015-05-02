<?php

namespace Campru\GuzzleBundle\DataCollector;

use Campru\GuzzleBundle\Subscriber\Stopwatch;
use GuzzleHttp\Subscriber\History;

/**
 * GuzzleDataCollector unit test.
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class GuzzleDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test to get the collector name.
     */
    public function testGetName()
    {
        $guzzleDataCollector = $this->createGuzzleCollector([], new \SplObjectStorage);

        $this->assertEquals('guzzle', $guzzleDataCollector->getName());
    }

    /**
     * Test an empty GuzzleDataCollector.
     */
    public function testCollectEmpty()
    {
        $guzzleDataCollector = $this->createGuzzleCollector([], new \SplObjectStorage);

        $request  = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $guzzleDataCollector->collect($request, $response);

        $this->assertEquals($guzzleDataCollector->getCalls(), []);
        $this->assertEquals($guzzleDataCollector->countErrors(), 0);
        $this->assertEquals($guzzleDataCollector->getMethods(), []);
        $this->assertEquals($guzzleDataCollector->getTotalTime(), 0);
    }

    /**
     * Test a DataCollector containing one valid call.
     *
     * HTTP response code 100+ and 200+.
     */
    public function testCollectValidCall()
    {
        $queryString = 'foo=bar';
        $queryParams = ['foo' => 'bar'];

        $callInfo     = ['total' => 150, 'connection' => 15];
        $callUrlQuery = $this->stubQuery($queryString, $queryParams);
        $callRequest  = $this->stubRequest('get', 'http', 'test.local', '/', $callUrlQuery);
        $callResponse = $this->stubResponse(200, 'OK', 'Hello world');

        $storage = new \SplObjectStorage();
        $storage->attach($callResponse, $callInfo);

        $call = $this->stubCall($callRequest, $callResponse);
        $guzzleDataCollector = $this->createGuzzleCollector([$call], $storage);

        $request  = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $guzzleDataCollector->collect($request, $response);

        $calls = $guzzleDataCollector->getCalls();

        $this->assertCount(1, $calls);
        $this->assertEquals($guzzleDataCollector->countErrors(), 0);
        $this->assertEquals($guzzleDataCollector->getMethods(), ['get' => 1]);
        $this->assertEquals($guzzleDataCollector->getTotalTime(), 150);

        $this->assertEquals(
            $calls[0],
            [
                'request'  => [
                    'headers'      => null,
                    'method'       => 'get',
                    'scheme'       => 'http',
                    'host'         => 'test.local',
                    'path'         => '/',
                    'query'        => $queryString,
                    'queryParams'  => $queryParams,
                    'body'         => '',
                ],
                'response' => [
                    'statusCode'   => 200,
                    'reasonPhrase' => 'OK',
                    'headers'      => null,
                    'body'         => 'Hello world',
                ],
                'time'     => [
                    'total'        => 150,
                    'connection'   => 15,
                ],
                'error'    => false,
            ]
        );
    }

    /**
     * Test a DataCollector containing one faulty call.
     *
     * HTTP response code 400+ & 500+.
     */
    public function testCollectErrorCall()
    {
        $queryString = 'foo=bar';
        $queryParams = ['foo' => 'bar'];

        $callInfo     = ['connection' => 15, 'total' => 150];
        $callUrlQuery = $this->stubQuery($queryString, $queryParams);
        $callRequest  = $this->stubRequest('post', 'http', 'test.local', '/', $callUrlQuery);
        $callResponse = $this->stubResponse(404, 'Not found', 'Oops');

        $storage = new \SplObjectStorage();
        $storage->attach($callResponse, $callInfo);

        $call = $this->stubCall($callRequest, $callResponse);
        $guzzleDataCollector = $this->createGuzzleCollector([$call], $storage);

        $request  = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $guzzleDataCollector->collect($request, $response);

        $this->assertCount(1, $guzzleDataCollector->getCalls());
        $this->assertEquals($guzzleDataCollector->countErrors(), 1);
        $this->assertEquals($guzzleDataCollector->getMethods(), ['post' => 1]);
        $this->assertEquals($guzzleDataCollector->getTotalTime(), 150);

        $calls = $guzzleDataCollector->getCalls();
        $this->assertEquals(
            $calls[0],
            [
                'request'  => [
                    'headers'      => null,
                    'method'       => 'post',
                    'scheme'       => 'http',
                    'host'         => 'test.local',
                    'path'         => '/',
                    'query'        => $queryString,
                    'queryParams'  => $queryParams,
                    'body'         => '',
                ],
                'response' => [
                    'statusCode'   => 404,
                    'reasonPhrase' => 'Not found',
                    'headers'      => null,
                    'body'         => 'Oops',
                ],
                'time'     => [
                    'total'        => 150,
                    'connection'   => 15,
                ],
                'error'    => true,
            ]
        );
    }

    /**
     * Test a DataCollector containing one call with request content.
     */
    public function testCollectBodyRequestCall()
    {
        $queryString = 'foo=bar';
        $queryParams = ['foo' => 'bar'];

        $callInfo     = ['connection' => 15, 'total' => 150];
        $callUrlQuery = $this->stubQuery($queryString, $queryParams);
        $callRequest  = $this->stubRequest('post', 'http', 'test.local', '/', $callUrlQuery, 'Request body string');
        $callResponse = $this->stubResponse(201, 'Created', '');

        $storage = new \SplObjectStorage();
        $storage->attach($callResponse, $callInfo);

        $call = $this->stubCall($callRequest, $callResponse);
        $guzzleDataCollector = $this->createGuzzleCollector([$call], $storage);

        $request  = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $guzzleDataCollector->collect($request, $response);

        $this->assertCount(1, $guzzleDataCollector->getCalls());
        $this->assertEquals($guzzleDataCollector->countErrors(), 0);
        $this->assertEquals($guzzleDataCollector->getMethods(), ['post' => 1]);
        $this->assertEquals($guzzleDataCollector->getTotalTime(), 150);

        $calls = $guzzleDataCollector->getCalls();
        $this->assertEquals(
            $calls[0],
           [
                'request'  => [
                    'headers'      => null,
                    'method'       => 'post',
                    'scheme'       => 'http',
                    'host'         => 'test.local',
                    'path'         => '/',
                    'query'        => $queryString,
                    'queryParams'  => $queryParams,
                    'body'         => 'Request body string',
                ],
                'response' => [
                    'statusCode'   => 201,
                    'reasonPhrase' => 'Created',
                    'headers'      => null,
                    'body'         => '',
                ],
                'time'     => [
                    'total'        => 150,
                    'connection'   => 15,
                ],
                'error'    => false,
            ]
        );

    }

    /**
     * Create the DataCollector.
     *
     * @param array $calls An array of calls.
     * @param \SplObjectStorage $storage Storage.
     * @return GuzzleDataCollector
     */
    private function createGuzzleCollector(array $calls, \SplObjectStorage $storage)
    {
        return new GuzzleDataCollector(new HistorySubscriberStub($calls), new StopwatchSubscriberStub($storage));
    }

    /**
     * Stub a Guzzle call (processed request).
     *
     * @param RequestInterface $request Guzzle request.
     * @param ResponseInterface $response Guzzle response.
     * @return array
     */
    private function stubCall($request, $response)
    {
        return [
            'request'  => $request,
            'response' => $response
        ];
    }

    /**
     * Stub a Guzzle Query.
     *
     * @param string $queryString Query string.
     * @param array $queryParams Array of url query parameters.
     * @return Query
     */
    private function stubQuery($queryString, array $queryParams)
    {
        $query = $this->getMock('\GuzzleHttp\Query');
        $query
            ->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($queryString));
        $query
            ->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue($queryParams));

        return $query;
    }

    /**
     * Stub a Guzzle request.
     *
     * @param string $method get, post.
     * @param string $scheme http, https.
     * @param string $host test.tld.
     * @param string $path /test.
     * @param Query $query Guzzle Query.
     * @param string $body Request body.
     * @return RequestInterface
     */
    private function stubRequest($method, $scheme, $host, $path, $query, $body = null)
    {
        $request = $this->getMock('\GuzzleHttp\Message\RequestInterface');
        $request
            ->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($method));
        $request
            ->expects($this->any())
            ->method('getScheme')
            ->will($this->returnValue($scheme));
        $request
            ->expects($this->any())
            ->method('getHost')
            ->will($this->returnValue($host));
        $request
            ->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));
        $request
            ->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $request
            ->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($body));

        return $request;
    }

    /**
     * Stub a Guzzle response.
     *
     * @param integer $code Status code.
     * @param string $reason Response reason phrase.
     * @param string $body Response body.
     * @return ResponseInterface
     */
    private function stubResponse($code, $reason, $body)
    {
        $response = $this->getMock('\GuzzleHttp\Message\ResponseInterface', [], [$code]);
        $response
            ->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue($code));
        $response
            ->expects($this->any())
            ->method('getReasonPhrase')
            ->will($this->returnValue($reason));
        $response
            ->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($body));

        return $response;
    }
}

/**
 * Fake History subscriber.
 */
class HistorySubscriberStub extends History implements \IteratorAggregate
{
    private $stubJournal = [];

    public function __construct(array $stubJournal)
    {
        $this->stubJournal = $stubJournal;
    }

    /**
     * Get the requests in the history
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->stubJournal);
    }
}

/**
 * Fake Stopwatch subscriber.
 */
class StopwatchSubscriberStub extends Stopwatch
{
    private $storage;

    public function __construct(\SplObjectStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get the storage from Stopwatch subscriber.
     *
     * @return \SplObjectStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }
}
