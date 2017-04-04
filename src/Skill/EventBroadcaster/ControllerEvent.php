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

    public function __construct(\Closure $callback, array $arguments = [])
    {
        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return mixed
     */
    public function callback(): \Closure
    {
        return $this->callback;
    }

    /**
     * @codeCoverageIgnore
     *
     * Set new callback to ControllerEvent. It must be callable.
     *
     * @param  mixed $callback The new callback to set
     * @return self
     * @throws \InvalidArgumentException if passed callback is not callable
     */
    public function setCallback(\Closure $callback): void
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
     * @param  array $arguments The new arguments to set, default: empty array ([])
     * @return self
     */
    public function setArguments(array $arguments = []): void
    {
        $this->arguments = $arguments;
    }
}
