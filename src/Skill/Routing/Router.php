<?php

declare(strict_types=1);

namespace Jarvis\Skill\Routing;

use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as Parser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Router extends Dispatcher
{
    const DEFAULT_SCHEME = 'http';
    const HTTP_PORT = 80;
    const HTTPS_PORT = 443;

    private $computed = false;
    private $host = '';
    private $rawRoutes = [];
    private $routesNames = [];
    private $routeCollector;
    private $scheme = self::DEFAULT_SCHEME;

    /**
     * Creates an instance of Router.
     *
     * Required to disable FastRoute\Dispatcher\GroupCountBased constructor.
     */
    public function __construct()
    {
    }

    /**
     * Adds a new route to the collection.
     *
     * We highly recommend you to use ::beginRoute() instead.
     * {@see ::beginRoute()}
     *
     * @param  Route $route
     * @return self
     */
    public function addRoute(Route $route): void
    {
        $this->rawRoutes[] = [$route->method(), $route->pattern(), $route->handler()];
        $this->computed = false;

        if (false != $name = $route->name()) {
            $this->routesNames[$name] = $route->pattern();
        }
    }

    /**
     * This is an helper that provides you a smooth syntax to add new route. Example:
     *
     * $router
     *     ->beginRoute('hello_world')
     *         ->setPattern('/hello/world')
     *         ->setHandler(function () {
     *             return 'Hello, world!';
     *         })
     *     ->end()
     * ;
     *
     * This syntax avoids you to create a new intance of Route, hydrating it and
     * then adding it to Router.
     *
     * @param  string|null $name
     * @return Route
     */
    public function beginRoute(string $name = null): Route
    {
        return new Route($this, $name);
    }

    /**
     * Generates and returns the full URL (with scheme and host) with provided URI.
     *
     * Notes that this method required at least the host to be setted.
     *
     * @param  string $uri
     * @return string
     */
    public function url(string $uri): string
    {
        $scheme = '';
        if ($this->host) {
            $uri = preg_replace('~/+~', '/', "{$this->host}$uri");
            $scheme = "{$this->scheme}://";
        }

        return "$scheme$uri";
    }

    /**
     * Returns the current scheme.
     *
     * @return string
     */
    public function scheme(): string
    {
        return $this->scheme;
    }

    /**
     * Sets the new scheme to use. Calling this method without parameter will reset
     * it to 'http'.
     *
     * @param string|null $scheme
     */
    public function setScheme(string $scheme = null): void
    {
        $this->scheme = (string) $scheme ?: self::DEFAULT_SCHEME;
    }

    /**
     * Returns the setted host.
     *
     * @return string
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * Sets new host to Router. Calling this method without parameter will reset
     * the host to empty string.
     *
     * @param  string|null $host
     * @return self
     */
    public function setHost(string $host = null): void
    {
        $this->host = (string) $host;
    }

    /**
     * Uses the provided request to guess the host. This method also set the
     *
     * @param  Request $request
     * @return self
     */
    public function guessHost(Request $request): void
    {
        $this->setScheme($request->getScheme());
        $this->setHost($request->getHost());
        if (!in_array($request->getPort(), [self::HTTP_PORT, self::HTTPS_PORT])) {
            $this->setHost($this->host() . ':' . $request->getPort());
        }
    }

    /**
     * Generates URI associated to provided route name.
     *
     * @param  string $name   The URI route name we want to generate
     * @param  array  $params Parameters to replace in pattern
     * @return string
     * @throws \InvalidArgumentException if provided route name is unknown
     */
    public function uri(string $name, array $params = []): string
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
                throw new \InvalidArgumentException(
                    "Parameter '{$key}' must match regex '{$pieces[1]}' for route '{$name}'."
                );
            }

            $uri = str_replace($matches[0], $value, $uri);
        }

        return $uri;
    }

    /**
     * Matches the given HTTP method and URI to the route collection and returns
     * the callback with the array of arguments to use.
     *
     * @param  string $method
     * @param  string $uri
     * @return array
     */
    public function match(string $method, string $uri): array
    {
        $arguments = [];
        $callback = null;
        $result = $this->dispatch($method, $uri);

        if (Dispatcher::FOUND === $result[0]) {
            [1 => $callback, 2 => $arguments] = $result;
        } else {
            $callback = function () use ($result): Response {
                return new Response(null, Dispatcher::NOT_FOUND === $result[0]
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_METHOD_NOT_ALLOWED
                );
            };
        }

        return [$callback, $arguments];
    }

    /**
     * {@inheritdoc}
     * Overrides GroupCountBased::dispatch() to ensure that dispatcher always deals with up-to-date
     * route collection.
     */
    public function dispatch($method, $uri): array
    {
        [$this->staticRouteMap, $this->variableRouteData] = $this->routeCollector()->getData();

        return parent::dispatch(strtolower($method), $uri);
    }

    /**
     * Will always return the right RouteCollector and knows when to recompute it.
     *
     * @return RouteCollector
     */
    private function routeCollector(): RouteCollector
    {
        if (!$this->computed) {
            $this->routeCollector = new RouteCollector(new Parser(), new DataGenerator());
            foreach ($this->rawRoutes as $rawRoute) {
                [$method, $route, $handler] = $rawRoute;
                $this->routeCollector->addRoute($method, $route, $handler);
            }

            $this->computed = true;
        }

        return $this->routeCollector;
    }
}
