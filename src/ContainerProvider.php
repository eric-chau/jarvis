<?php

namespace Jarvis;

use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Jarvis\Component\Router;
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
    public static function hydrate(Jarvis $container)
    {
        $container['request'] = function () {
            return Request::createFromGlobals();
        };

        $container['route_collector'] = function () {
            return new RouteCollector(new Std(), new GroupCountBased());
        };

        $container['url_matcher'] = function ($jarvis) {
            return new Dispatcher($jarvis['route_collector']->getData());
        };
    }
}
