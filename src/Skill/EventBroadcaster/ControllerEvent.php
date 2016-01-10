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
        $this->callback = $this->validateCallback($callback);
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
     * Set new callback to ControllerEvent. It must be callable.
     *
     * @param  mixed $callback The new callback to set
     * @return self
     * @throws \InvalidArgumentException if passed callback is not callable
     */
    public function setCallback($callback) : ControllerEvent
    {
        $this->callback = $this->validateCallback($callback);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function arguments() : array
    {
        return $this->arguments;
    }

    /**
     * @codeCoverageIgnore
     *
     * Sets new list of arguments to ControllerEvent.
     *
     * @param  array $arguments The new arguments to set, default: empty array ([])
     * @return self
     */
    public function setArguments(array $arguments = []) : ControllerEvent
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Validates provided callback and throws exception if it is not callable.
     *
     * @param  mixed $callback The callback to validate
     * @return mixed
     * @throws \InvalidArgumentException if passed callback is not callable
     */
    public function validateCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Provided callback is not callable.');
        }

        return $callback;
    }
}
