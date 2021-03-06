<?php

declare(strict_types=1);

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
 * @author Eric Chau <eriic.chau@gmail.com>
 */
final class ContainerProvider implements ContainerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(Jarvis $app)
    {
        $this
            ->mountCoreComponents($app)
            ->mountHttpComponents($app)
            ->mountCallbackResolverComponents($app)
        ;
    }

    protected function mountCoreComponents(Jarvis $app)
    {
        $app['app'] = function () use ($app): Jarvis {
            return $app;
        };

        $app['debug'] = $app['settings']['debug'] ?? Jarvis::DEFAULT_DEBUG;
        $app->lock('debug');

        $extra = $app['settings']['extra'] ?? [];
        foreach ((array) $extra as $key => $data) {
            $id = sprintf('%s.settings', $key);
            $app[$id] = $data;
            $app->lock($id);
        }

        unset($app['settings']);
        $app->lock('app');

        return $this;
    }

    protected function mountHttpComponents(Jarvis $app)
    {
        $app['request'] = function (Jarvis $app): Request {
            $request = Request::createFromGlobals();

            if (null === $session = $request->getSession()) {
                $settings = $app['session.settings'] ?? [];
                $storageClassname = $settings['session.storage.classname'] ?? NativeSessionStorage::class;
                unset($settings['session.storage.classname']);
                $session = new Session(new $storageClassname($settings));
            }

            $request->setSession($session);

            return $request;
        };

        $app['session'] = function (Jarvis $app): Session {
            return $app[Request::class]->getSession();
        };

        $app['router'] = function (): Router {
            return new Router();
        };

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

        $app->lock(['request', 'session', 'router']);

        return $this;
    }

    protected function mountCallbackResolverComponents(Jarvis $app)
    {
        $app['callbackResolver'] = function (Jarvis $app): CallbackResolver {
            return new CallbackResolver($app);
        };

        $app->on(BroadcasterInterface::CONTROLLER_EVENT, function (ControllerEvent $event) use ($app): void {
            $event->setCallback($app[CallbackResolver::class]->toClosure($event->callback()));
            $event->setArguments($app[CallbackResolver::class]->resolveArgumentsForClosure(
                $event->callback(),
                $event->arguments()
            ));
        }, BroadcasterInterface::RECEIVER_LOW_PRIORITY);

        $app->lock('callbackResolver');

        return $this;
    }
}
