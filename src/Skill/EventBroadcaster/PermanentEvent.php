<?php

declare(strict_types = 1);

namespace Jarvis\Skill\EventBroadcaster;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class PermanentEvent extends SimpleEvent implements PermanentEventInterface
{
    /**
     * {@inheritdoc}
     */
    public function isPermanent() : bool
    {
        return true;
    }
}
