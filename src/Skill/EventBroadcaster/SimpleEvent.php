<?php

declare(strict_types=1);

namespace Jarvis\Skill\EventBroadcaster;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class SimpleEvent implements EventInterface
{
    private $isPropagationStopped = false;

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->isPropagationStopped = true;
    }
}
