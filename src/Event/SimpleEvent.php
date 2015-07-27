<?php

namespace Jarvis\Event;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class SimpleEvent implements EventInterface
{
    private $isPropagationStopped = false;

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped()
    {
        return $this->isPropagationStopped;
    }

    public function stopPropagation()
    {
        $this->isPropagationStopped = true;

        return $this;
    }
}
