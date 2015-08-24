<?php

namespace Jarvis\Tests;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class FakeReceiver
{
    public $event;
    public $analyzeEvent;
    public $controllerEvent;
    public $responseEvent;

    public function onEventBroadcast($event)
    {
        $this->event = $event;
    }

    public function onAnalyzeEvent($event)
    {
        $this->analyzeEvent = $event;
    }

    public function onControllerEvent($event)
    {
        $this->controllerEvent = $event;
    }

    public function onResponseEvent($event)
    {
        $this->responseEvent = $event;
    }

    public function onAnalyzeEventSetResponse($event)
    {
        $this->analyzeEvent = $event;
        $event->setResponse(new Response(null, Response::HTTP_NOT_MODIFIED));
    }

    public function modifyResponseOnResponseEvent($event)
    {
        $this->responseEvent = $event;
        $event->getResponse()->setContent('bar');
    }
}
