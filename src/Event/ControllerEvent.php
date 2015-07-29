<?php

namespace Jarvis\Event;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerEvent extends SimpleEvent
{
    private $controller;

    public function __construct(array $controller)
    {
        $this->controller = $controller;
    }

    public function getController()
    {
        return $this->controller;
    }
}
