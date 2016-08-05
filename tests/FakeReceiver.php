<?php

namespace Jarvis\Tests;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class FakeReceiver
{
    public $event;
    public $runEvent;
    public $controllerEvent;
    public $responseEvent;
    public $microTimestamp;

    public function onEventBroadcast($event)
    {
        $this->event = $event;
    }

    public function onRunEvent($event)
    {
        $this->runEvent = $event;
    }

    public function onControllerEvent($event)
    {
        $this->controllerEvent = $event;
    }

    public function onResponseEvent($event)
    {
        $this->responseEvent = $event;
    }

    public function onRunEventSetResponse($event)
    {
        $this->runEvent = $event;
        $event->setResponse(new Response(null, Response::HTTP_NOT_MODIFIED));
    }

    public function modifyResponseOnResponseEvent($event)
    {
        $this->responseEvent = $event;
        $event->response()->setContent('bar');
    }

    public function saveMicroTimestamp($event)
    {
        $this->microTimestamp = microtime(true);
        usleep(1);
    }
}
