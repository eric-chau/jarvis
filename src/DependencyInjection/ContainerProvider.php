<?php

namespace Jarvis\DependencyInjection;

use Jarvis\Skill\Core\CallbackResolver;
use Jarvis\Skill\Core\ScopeManager;
use Jarvis\Annotation\Parser;
use Jarvis\Annotation\Handler\ResponseFormatHandler;
use Jarvis\Event\JarvisEvents;
use Jarvis\Event\Receiver\ControllerReceiver;
use Jarvis\Jarvis;
use Jarvis\Relational\Annotation\Handler\ParamConverterHandler;
use Jarvis\Rest\EventReceiver\RestReceiver;
use Jarvis\Routing\Router;
use Minime\Annotations\Reader;
use Minime\Annotations\Cache\ArrayCache;
use Minime\Annotations\Cache\FileCache;
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

        $jarvis['annotation_reader'] = function ($jarvis) {
            $cache = null;
            if (isset($jarvis['settings']['cache_dir']) && is_writable($jarvis['settings']['cache_dir'])) {
                $cache = new FileCache($jarvis['settings']['cache_dir']);
            }

            return new Reader(new Parser, $cache ?: new ArrayCache);
        };

        $jarvis['scope_manager'] = function () {
            return new ScopeManager();
        };

        $jarvis->lock(['request', 'router', 'callback_resolver', 'annotation_reader']);
    }

    protected static function injectEventReceivers(Jarvis $jarvis)
    {
        // Rest receiver
        $jarvis['jarvis.rest_receiver'] = function ($jarvis) {
            return new RestReceiver(
                $jarvis['scope_manager'],
                isset($jarvis['settings']['rest']) ? (array) $jarvis['settings']['rest'] : []
            );
        };

        $jarvis->addReceiver(JarvisEvents::ANALYZE_EVENT, [
            new Reference('jarvis.rest_receiver'),
            'onAnalyzeEvent',
        ]);


        // jarvis.controller event receiver
        $jarvis['jarvis.controller_receiver'] = function ($jarvis) {
            return new ControllerReceiver($jarvis);
        };

        $jarvis->addReceiver(JarvisEvents::CONTROLLER_EVENT, [
            new Reference('jarvis.controller_receiver'),
            'onControllerEvent',
        ]);

        $jarvis->lock(['jarvis.rest_receiver', 'jarvis.controller_receiver']);
    }

    protected static function injectAnnotationHandlers(Jarvis $jarvis)
    {
        $jarvis['annotation.handler.response_format'] = function ($jarvis) {
            return new ResponseFormatHandler($jarvis['request']);
        };

        $jarvis['annotation.handler.param_converter'] = function ($jarvis) {
            return new ParamConverterHandler($jarvis['request'], $jarvis['mapper']);
        };
    }
}
