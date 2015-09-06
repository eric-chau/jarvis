<?php

namespace Jarvis\Skill\EventBroadcaster;

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
