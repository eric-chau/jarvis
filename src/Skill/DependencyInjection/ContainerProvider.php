<?php

declare(strict_types = 1);

namespace Jarvis\Skill\DependencyInjection;

use Jarvis\Jarvis;
use Jarvis\Skill\Core\CallbackResolver;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
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
    public function hydrate(Jarvis $app)
    {
        $app['app'] = function () use ($app): Jarvis {
            return $app;
        };

        $app['request'] = function (): Request {
            $request = Request::createFromGlobals();
            $request->setSession($request->getSession() ?? new Session());

            return $request;
        };

        $app['session'] = function (Jarvis $app): Session {
            return $app->request->getSession();
        };

        $app['router'] = function (): Router {
            return new Router();
        };

        $app['callbackResolver'] = function (Jarvis $app): CallbackResolver {
            return new CallbackResolver($app);
        };

        $app->lock(['request', 'session', 'router', 'callbackResolver']);

        $app->on(BroadcasterInterface::EXCEPTION_EVENT, function (ExceptionEvent $event): void {
            $response = new Response($event->exception()->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            $event->setResponse($response);
        }, BroadcasterInterface::RECEIVER_LOW_PRIORITY);

        $app->on(BroadcasterInterface::CONTROLLER_EVENT, function (ControllerEvent $event) use ($app): void {
            $finalArgs = [];
            $rawArgs = $event->arguments();
            $refMethod = new \ReflectionMethod($event->callback(), '__invoke');
            foreach ($refMethod->getParameters() as $refParam) {
                if (null !== $refClass = $refParam->getClass()) {
                    if (isset($app[$refClass->getName()])) {
                        $finalArgs[$refParam->getPosition()] = $app[$refClass->getName()];

                        continue;
                    }
                }

                if (in_array($refParam->name, array_keys($rawArgs))) {
                    $finalArgs[$refParam->getPosition()] = $rawArgs[$refParam->name];
                }
            }

            $event->setArguments($finalArgs);
        }, BroadcasterInterface::RECEIVER_LOW_PRIORITY);
    }
}
