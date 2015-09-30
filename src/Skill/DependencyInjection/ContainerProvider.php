<?php

namespace Jarvis\Skill\DependencyInjection;

use Jarvis\Jarvis;
use Jarvis\Skill\Core\CallbackResolver;
use Jarvis\Skill\Core\ScopeManager;
use Jarvis\Skill\EventBroadcaster\JarvisEvents;
use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use Jarvis\Skill\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is Jarvis internal container provider. It will inject every core
 * parameters and services into Jarvis.
 *
 * @author Eric Chau <eric.chau@gmail.com>
 */
class ContainerProvider implements ContainerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(Jarvis $jarvis)
    {
        $jarvis['request'] = function($jarvis) {
            if (
                !is_string($classname = $jarvis->settings->get('request_fqcn', Request::class))
                || (
                    $classname !== Request::class
                    && !is_subclass_of($classname, Request::class)
                )
            ) {
                throw new \InvalidArgumentException(sprintf(
                    '"request_fqcn" parameter must be string and instance of %s.',
                    Request::class
                ));
            }

            return $classname::createFromGlobals();
        };

        $jarvis['router'] = function($jarvis) {
            return new Router($jarvis['scope_manager']);
        };

        $jarvis['callback_resolver'] = function($jarvis) {
            return new CallbackResolver($jarvis);
        };

        $jarvis['scope_manager'] = function() {
            return new ScopeManager();
        };

        $jarvis->lock(['request', 'router', 'callback_resolver']);

        $this->registerReceivers($jarvis);
    }

    private function registerReceivers(Jarvis $jarvis)
    {
        $jarvis->addReceiver(JarvisEvents::EXCEPTION_EVENT, function(ExceptionEvent $event) {
            $response = new Response($event->getException()->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            $event->setResponse($response);
        }, Jarvis::RECEIVER_LOW_PRIORITY);
    }
}
