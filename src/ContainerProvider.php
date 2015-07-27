<?php

namespace Jarvis;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Jarvis\Routing\Router;
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
    public static function hydrate(Jarvis $container)
    {
        $container['request'] = function () {
            return Request::createFromGlobals();
        };

        $container['router'] = function () {
            return new Router(new RouteCollector(new Std(), new GroupCountBased()));
        };

        $container->lock(['request', 'router']);
    }
}
