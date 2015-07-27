<?php

namespace Jarvis\Routing;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Router extends GroupCountBased
{
    private $routeCollector;

    public function __construct(RouteCollector $routeCollector)
    {
        $this->routeCollector = $routeCollector;
    }

    /**
     * Alias to Router's route collector ::addRoute method.
     * @see RouteCollector::addRoute
     */
    public function addRoute($httpMethod, $route, $handler)
    {
        $this->routeCollector->addRoute($httpMethod, $route, $handler);

        return $this;
    }

    /**
     * Alias of GroupCountBased::dispatch.
     * {@inheritdoc}
     */
    public function match($httpMethod, $uri)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $this->routeCollector->getData();

        return $this->dispatch($httpMethod, $uri);
    }
}
