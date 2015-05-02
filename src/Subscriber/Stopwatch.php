<?php

namespace Campru\GuzzleBundle\Subscriber;

use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * Stopwatch.
 *
 * @author David Camprubí <david.camprubi@gmail.com>
 */
class Stopwatch implements SubscriberInterface
{
    private $storage;

    /**
     * Construct the Stopwatch subscriber.
     *
     * @param \SplObjectStorage $storage
     */
    public function __construct(\SplObjectStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get the event names that this subscriber wants to listen to.
     *
     * @return array
     */
    public function getEvents()
    {
        return [
            'end' => ['onEnd', RequestEvents::EARLY],
        ];
    }

    /**
     * Manage the event emitted when a request transaction has ended.
     *
     * @param EndEvent $event Event emitted.
     */
    public function onEnd(EndEvent $event)
    {
        $response = $event->getResponse();
        $data     = [
            'total'      => $event->getTransferInfo('total_time'),
            'connection' => $event->getTransferInfo('connect_time'),
        ];
        $this->storage->attach($response, $data);
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