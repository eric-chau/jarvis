<?php

namespace Jarvis\Component;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerResolver
{
    private $jarvis;

    public function __construct(\Jarvis $jarvis)
    {
        $this->jarvis = $jarvis;
    }

    public function resolve(array $parameters)
    {
        $callback = $controller = null;
        if ('@' === $parameters['_controller'][0]) {
            $key = str_replace('@', '', $parameters['_controller']);
            $controller = isset($this->jarvis[$key]) ? $this->jarvis[$key] : null;
        }

        if (is_object($controller) && method_exists($controller, $parameters['_action'])) {
            $callback = [$controller, $parameters['_action']];
        }

        return $callback ?: $this->jarvis['fallback.action'];
    }
}
