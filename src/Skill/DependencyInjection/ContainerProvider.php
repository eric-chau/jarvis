<?php

namespace Jarvis\Skill\DependencyInjection;

use Jarvis\Jarvis;
use Jarvis\Skill\Core\CallbackResolver;
use Jarvis\Skill\Core\ScopeManager;
use Jarvis\Skill\EventBroadcaster\JarvisEvents;
use Jarvis\Skill\EventBroadcaster\Receiver\ExceptionReceiver;
use Jarvis\Skill\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

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
    public static function hydrate(Jarvis $jarvis)
    {
        $jarvis['request'] = function ($jarvis) {
            $classname = isset($jarvis['request_fqcn']) ? $jarvis['request_fqcn'] : Request::class;

            if (
                !is_string($classname)
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

        $jarvis['router'] = function ($jarvis) {
            return new Router($jarvis['scope_manager']);
        };

        $jarvis['callback_resolver'] = function ($jarvis) {
            return new CallbackResolver($jarvis);
        };

        $jarvis['scope_manager'] = function () {
            return new ScopeManager();
        };

        $jarvis['jarvis.exception_receiver'] = function () {
            return new ExceptionReceiver();
        };

        $jarvis->addReceiver(JarvisEvents::EXCEPTION_EVENT, [
            new Reference('jarvis.exception_receiver'),
            'onExceptionEvent',
        ]);

        $jarvis->lock(['request', 'router', 'callback_resolver', 'jarvis.exception_receiver']);
    }
}
