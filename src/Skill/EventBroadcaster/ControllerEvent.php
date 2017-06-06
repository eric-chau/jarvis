<?php

declare(strict_types=1);

namespace Jarvis\Skill\EventBroadcaster;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerEvent extends SimpleEvent
{
    private $callback;
    private $arguments;

    public function __construct($callback, array $arguments = [])
    {
        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return mixed
     */
    public function callback()
    {
        return $this->callback;
    }

    /**
     * @codeCoverageIgnore
     *
     * Set new callback to ControllerEvent. It must be callable.
     *
     * @param mixed $callback
     */
    public function setCallback($callback): void
    {
        $this->callback = $callback;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @codeCoverageIgnore
     *
     * Sets new list of arguments to ControllerEvent.
     *
     * @param array $arguments
     */
    public function setArguments(array $arguments = []): void
    {
        $this->arguments = $arguments;
    }
}
