<?php

declare(strict_types=1);

namespace Jarvis\Skill\EventBroadcaster;

/**
 * Permanent event is an event that can be invoked on new receiver even if the
 * event was already dispatched.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
interface PermanentEventInterface extends EventInterface
{
    /**
     * @return boolean
     */
    public function isPermanent(): bool;
}
