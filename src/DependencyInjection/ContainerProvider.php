<?php

namespace Jarvis\DependencyInjection;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Jarvis\Ability\CallbackResolver;
use Jarvis\Annotations\Parser;
use Jarvis\Event\JarvisEvents;
use Jarvis\Jarvis;
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
        $jarvis['request'] = function () {
            return Request::createFromGlobals();
        };

        $jarvis['router'] = function () {
            return new Router(new RouteCollector(new Std(), new GroupCountBased()));
        };

        $jarvis['jarvis.rest_receiver'] = function ($jarvis) {
            return new RestReceiver($jarvis);
        };

        $jarvis['callback_resolver'] = function ($jarvis) {
            return new CallbackResolver($jarvis);
        };

        $jarvis['annotation_reader'] = function ($jarvis) {
            $cache = null;
            if (isset($jarvis['jarvis.settings']['cache_dir']) && is_writable($jarvis['jarvis.settings']['cache_dir'])) {
                $cache = new FileCache($jarvis['jarvis.settings']['cache_dir']);
            }

            return new Reader(new Parser, $cache ?: new ArrayCache);
        };

        $reference = new Reference('jarvis.rest_receiver');
        $jarvis->addReceiver(JarvisEvents::ANALYZE_EVENT, [$reference, 'onAnalyzeEvent']);
        $jarvis->addReceiver(JarvisEvents::CONTROLLER_EVENT, [$reference, 'onControllerEvent']);

        $jarvis->lock(['request', 'router', 'jarvis.rest_receiver', 'callback_resolver', 'annotation_reader']);
    }
}
