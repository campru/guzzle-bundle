<?php

namespace Campru\GuzzleBundle\DataCollector;

use Campru\GuzzleBundle\Subscriber\Stopwatch;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Subscriber\History;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * GuzzleDataCollector.
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class GuzzleDataCollector extends DataCollector
{
    private $profiler;
    private $storage;

    /**
     * Construct the data collector.
     *
     * @param History $history History subscriber.
     * @param Stopwatch $stopwatch Stopwatch subscriber.
     */
    public function __construct(History $history, Stopwatch $stopwatch)
    {
        $this->profiler = $history;
        $this->storage  = $stopwatch->getStorage();
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request $request HTTP request.
     * @param Response $response HTTP response.
     * @param \Exception $exception Exception.
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $data = [
            'calls'       => [],
            'error_count' => 0,
            'methods'     => [],
            'total_time'  => 0,
        ];

        // Aggregates global metrics about Guzzle usage.
        $aggregate = function ($request, $time, $error) use (&$data) {
            $method = $request['method'];
            if (!isset($data['methods'][$method])) {
                $data['methods'][$method] = 0;
            }

            $data['methods'][$method]++;
            $data['total_time'] += $time['total'];
            $data['error_count'] += (int) $error;
        };

        foreach ($this->profiler as $call) {
            $request  = $this->collectRequest($call['request']);
            $response = $this->collectResponse($call['response']);
            $time     = $this->collectTime($call['response']);
            $error    = $this->isError($call['response']);

            $aggregate($request, $time, $error);

            $data['calls'][] = [
                'request'  => $request,
                'response' => $response,
                'time'     => $time,
                'error'    => $error,
            ];
        }

        $this->data = $data;
    }

    /**
     * Get all HTTP calls.
     *
     * @return array
     */
    public function getCalls()
    {
        return isset($this->data['calls']) ? $this->data['calls'] : [];
    }

    /**
     * Count the HTTP call errors.
     *
     * @return integer
     */
    public function countErrors()
    {
        return isset($this->data['error_count']) ? $this->data['error_count'] : 0;
    }

    /**
     * Get the HTTP methods of the requests.
     *
     * @return array
     */
    public function getMethods()
    {
        return isset($this->data['methods']) ? $this->data['methods'] : [];
    }

    /**
     * Get the total time of all HTTP calls.
     *
     * @return integer
     */
    public function getTotalTime()
    {
        return isset($this->data['total_time']) ? $this->data['total_time'] : 0;
    }

    /**
     * Get the collector name.
     *
     * @return string
     */
    public function getName()
    {
        return 'guzzle';
    }

    /**
     * Collect & sanitize data about a Guzzle request.
     *
     * @param RequestInterface $request Guzzle request.
     * @return array
     */
    private function collectRequest(RequestInterface $request)
    {
        $query = $request->getQuery();

        return [
            'headers'     => $request->getHeaders(),
            'method'      => $request->getMethod(),
            'scheme'      => $request->getScheme(),
            'host'        => $request->getHost(),
            'path'        => $request->getPath(),
            'query'       => (string) $query,
            'queryParams' => $query->toArray(),
            'body'        => (string) $request->getBody(),
        ];
    }

    /**
     * Collect & sanitize data about a Guzzle response.
     *
     * @param ResponseInterface $response Guzzle response.
     * @return array
     */
    private function collectResponse(ResponseInterface $response)
    {
        return [
            'statusCode'   => $response->getStatusCode(),
            'reasonPhrase' => $response->getReasonPhrase(),
            'headers'      => $response->getHeaders(),
            'body'         => (string) $response->getBody(),
        ];
    }

    /**
     * Collect time for a Guzzle response.
     *
     * @param ResponseInterface $response Guzzle response.
     * @return array
     */
    private function collectTime(ResponseInterface $response)
    {
        return $this->storage->offsetExists($response) ?
            $this->storage->offsetGet($response) :
            ['total' => 0, 'connection' => 0];
    }

    /**
     * Checks if HTTP Status code is Server OR Client Error (4xx or 5xx)
     *
     * @param ResponseInterface $response Guzzle response.
     * @return boolean
     */
    private function isError(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();

        return $this->isClientError($statusCode) || $this->isServerError($statusCode);
    }

    /**
     * Checks if HTTP Status code is a Client Error (4xx).
     *
     * @param integer $statusCode Status code.
     * @return boolean
     */
    private function isClientError($statusCode)
    {
        return $statusCode >= 400 && $statusCode < 500;
    }

    /**
     * Checks if HTTP Status code is Server Error (5xx).
     *
     * @param integer $statusCode Status code.
     * @return boolean
     */
    private function isServerError($statusCode)
    {
        return $statusCode >= 500 && $statusCode < 600;
    }
}
