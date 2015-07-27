<?php

namespace Jarvis\Event;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
interface EventInterface
{
    /**
     * @return boolean
     */
    public function isPropagationStopped();
}
