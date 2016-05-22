<?php

declare(strict_types = 1);

namespace Jarvis\Skill\Routing;

use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteParser\Std as Parser;
use FastRoute\RouteCollector;
use Jarvis\Jarvis;
use Jarvis\Skill\Core\ScopeManager;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Router extends Dispatcher
{
    private $rawRoutes = [];
    private $routesNames = [];
    private $routeCollector;
    private $compilationKey;
    private $scopeManager;
    private $host = '';
    private $scheme = 'http';

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    public function host() : string
    {
        return $this->host;
    }

    public function setHost(string $host = null) : Router
    {
        $this->host = (string) $host;

        return $this;
    }

    public function scheme() : string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme = null) : Router
    {
        $this->scheme = (string) $scheme ?: 'http';

        return $this;
    }

    /**
     * Alias to Router's route collector ::addRoute method.
     * @see RouteCollector::addRoute
     */
    public function addRoute(Route $route) : Router
    {
        $this->rawRoutes[$route->scope()] = $this->rawRoutes[$route->scope()] ?? [];
        $this->rawRoutes[$route->scope()][] = [$route->method(), $route->pattern(), $route->handler()];
        $this->compilationKey = null;

        if (null !== $name = $route->name()) {
            $this->routesNames[$name] = $route->pattern();
        }

        return $this;
    }

    public function beginRoute(string $name = null) : Route
    {
        return new Route($name, $this);
    }

    public function url(string $uri) : string
    {
        $scheme = '';
        if ($this->host) {
            $uri = preg_replace('~/+~', '/', "{$this->host}$uri");
            $scheme = "{$this->scheme}://";
        }

        return "$scheme$uri";
    }

    /**
     * Generates URI associated to provided route name.
     *
     * @param  string $name   The URI route name we want to generate
     * @param  array  $params Parameters to replace in pattern
     * @return string
     * @throws \InvalidArgumentException if provided route name is unknown
     */
    public function uri(string $name, array $params = []) : string
    {
        if (!isset($this->routesNames[$name])) {
            throw new \InvalidArgumentException(
                "Cannot generate URI for '$name' cause it does not exist."
            );
        }

        $uri = $this->routesNames[$name];
        foreach ($params as $key => $value) {
            if (1 !== preg_match("~\{($key:?[^}]*)\}~", $uri, $matches)) {
                continue;
            }

            $value = (string) $value;
            $pieces = explode(':', $matches[1]);
            if (1 < count($pieces) && 1 !== preg_match("~{$pieces[1]}~", $value)) {
                continue;
            }

            $uri = str_replace($matches[0], $value, $uri);
        }

        return $uri;
    }

    /**
     * Alias of GroupCountBased::dispatch.
     * {@inheritdoc}
     */
    public function match(string $method, string $uri)
    {
        return $this->dispatch($method, $uri);
    }

    public function dispatch($method, $uri)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $this->routeCollector()->getData();

        return parent::dispatch(strtolower($method), $uri);
    }

    private function routeCollector() : RouteCollector
    {
        $key = $this->generateCompilationKey();
        if (null === $this->compilationKey || $this->compilationKey !== $key) {
            $this->compilationKey = $key;
            $this->routeCollector = new RouteCollector(new Parser(), new DataGenerator());

            $enabledRoutes = [];
            foreach ($this->rawRoutes as $scope => $rawRoutes) {
                if ($this->scopeManager->isEnabled($scope)) {
                    $enabledRoutes = array_merge($enabledRoutes, $rawRoutes);
                }
            }

            foreach ($enabledRoutes as $rawRoute) {
                list($method, $route, $handler) = $rawRoute;
                $this->routeCollector->addRoute($method, $route, $handler);
            }
        }

        return $this->routeCollector;
    }

    private function generateCompilationKey() : string
    {
        return md5(implode(',', $this->scopeManager->all()));
    }
}
