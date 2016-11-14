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
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

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
        $this->mountSettings($app);
        $this->mountServices($app);
        $this->mountEventReceivers($app);
    }

    protected function mountSettings(Jarvis $app)
    {
        $app['debug'] = $app['settings']['debug'] ?? Jarvis::DEFAULT_DEBUG;
        $app->lock('debug');

        $extra = $app['settings']['extra'] ?? [];
        foreach ((array) $extra as $key => $data) {
            $app["{$key}.settings"] = $data;
            $app->lock("{$key}.settings");
        }

        unset($app['settings']);
    }

    protected function mountServices(Jarvis $app)
    {
        $app['app'] = function () use ($app): Jarvis {
            return $app;
        };

        $app['request'] = function (Jarvis $app): Request {
            $request = Request::createFromGlobals();

            $session = $request->getSession();
            if (null === $session) {
                $settings = $app['session.settings'] ?? [];
                $storageClassname = $settings['session.storage.classname'] ?? NativeSessionStorage::class;
                unset($settings['session.storage.classname']);
                $session = new Session(new $storageClassname($settings));
            }

            $request->setSession($session);

            return $request;
        };

        $app['session'] = function (Jarvis $app): Session {
            return $app['request']->getSession();
        };

        $app['router'] = function (): Router {
            return new Router();
        };

        $app['callbackResolver'] = function (Jarvis $app): CallbackResolver {
            return new CallbackResolver($app);
        };

        $app->lock(['app', 'request', 'session', 'router', 'callbackResolver']);
    }

    protected function mountEventReceivers(Jarvis $app)
    {
        $app->on(BroadcasterInterface::EXCEPTION_EVENT, function (ExceptionEvent $event) use ($app): void {
            $throwable = $event->exception();
            $msg = sprintf(
                '[%s] error in %s at line %s with message: %s',
                get_class($throwable),
                $throwable->getFile(),
                $throwable->getLine(),
                $throwable->getMessage()
            );

            if (!$app['debug']) {
                error_log($msg);
                $msg = '';
            }

            $event->setResponse(new Response($msg, Response::HTTP_INTERNAL_SERVER_ERROR));
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
