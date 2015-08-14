<?php

namespace Jarvis\Skill\EventBroadcaster;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerEvent extends SimpleEvent
{
    public $controller;
    public $action;
    public $arguments;

    public function __construct($controller, $action, $arguments)
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->arguments = $arguments;
    }
}
