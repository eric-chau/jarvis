<?php

declare(strict_types = 1);

namespace Jarvis\Skill\Routing;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Route
{
    private $name;
    private $method = ['get'];
    private $pattern = '/';
    private $handler;
    private $router;

    public function __construct(string $name = null, Router $router)
    {
        $this->name = $name;
        $this->router = $router;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function method(): array
    {
        return $this->method;
    }

    public function setMethod($method): Route
    {
        $this->method = array_map('strtolower', (array) $method);

        return $this;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function setPattern(string $pattern): Route
    {
        $this->pattern = $pattern;

        return $this;
    }

    public function handler()
    {
        return $this->handler;
    }

    public function setHandler($handler): Route
    {
        $this->handler = $handler;

        return $this;
    }

    public function end(): Router
    {
        return $this->router->addRoute($this);
    }
}
