<?php

namespace Jarvis\Skill\EventBroadcaster\Receiver;

use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ExceptionReceiver
{
    public function onExceptionEvent(ExceptionEvent $event)
    {
        $event->setResponse(new Response($event->getException()->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR));
    }
}
