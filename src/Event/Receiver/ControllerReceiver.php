<?php

namespace Jarvis\Event\Receiver;

use Jarvis\Annotation\AnnotationHandlerInterface;
use Jarvis\Event\ControllerEvent;
use Jarvis\Jarvis;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerReceiver
{
    const ANNOTATION_HANDLER_SERVICE_BASE_IDENTIFIER = 'annotation.handler';

    private $jarvis;

    public function __construct(Jarvis $jarvis)
    {
        $this->jarvis = $jarvis;
    }

    public function onControllerEvent(ControllerEvent $event)
    {
        $request = $this->jarvis['request'];
        $request->attributes->add((array) $event->arguments);
        $annotations = array_merge(
            $this->jarvis['annotation_reader']->getClassAnnotations($event->controller)->toArray(),
            $this->jarvis['annotation_reader']->getMethodAnnotations($event->controller, $event->action)->toArray()
        );

        $handlers = $this->jarvis->find(self::ANNOTATION_HANDLER_SERVICE_BASE_IDENTIFIER.'*');
        foreach ($annotations as $annotation) {
            foreach ($handlers as $handler) {
                if ($handler instanceof AnnotationHandlerInterface && $handler->supports($annotation)) {
                    $handler->handle($annotation);
                }
            }
        }

        $arguments = [];
        $reflectionMethod = new \ReflectionMethod($event->controller, $event->action);
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if ($request->attributes->has($name = $reflectionParameter->getName())) {
                $arguments[$name] = $request->attributes->get($name);
            }
        }

        $event->arguments = $arguments;
    }
}
