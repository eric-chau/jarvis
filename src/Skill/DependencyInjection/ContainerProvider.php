<?php

declare(strict_types = 1);

namespace Jarvis\Skill\DependencyInjection;

use Jarvis\Jarvis;
use Jarvis\Skill\Core\CallbackResolver;
use Jarvis\Skill\Core\ScopeManager;
use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use Jarvis\Skill\EventBroadcaster\JarvisEvents;
use Jarvis\Skill\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This is Jarvis internal container provider. It will inject every core
 * parameters and services into Jarvis.
 *
 * @author Eric Chau <eric.chau@gmail.com>
 */
final class ContainerProvider implements ContainerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(Jarvis $jarvis)
    {
        $jarvis['request'] = function(Jarvis $jarvis) : Request {
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

            $request = $classname::createFromGlobals();
            $request->setSession($request->getSession() ?? new Session());

            return $request;
        };

        $jarvis['session'] = function(Jarvis $jarvis) : Session {
            return $jarvis->request->getSession();
        };

        $jarvis['router'] = function(Jarvis $jarvis) : Router {
            return new Router($jarvis['scopeManager']);
        };

        $jarvis['callbackResolver'] = function(Jarvis $jarvis) : CallbackResolver {
            return new CallbackResolver($jarvis);
        };

        $jarvis['scopeManager'] = function() : ScopeManager {
            return new ScopeManager();
        };

        $jarvis->lock(['request', 'session', 'router', 'callbackResolver', 'scopeManager']);

        $this->registerReceivers($jarvis);
    }

    private function registerReceivers(Jarvis $jarvis)
    {
        $jarvis->addReceiver(JarvisEvents::EXCEPTION_EVENT, function(ExceptionEvent $event) {
            $response = new Response($event->exception()->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            $event->setResponse($response);
        }, Jarvis::RECEIVER_LOW_PRIORITY);
    }
}
