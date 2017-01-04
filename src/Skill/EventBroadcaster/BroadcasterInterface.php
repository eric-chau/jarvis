<?php

declare(strict_types=1);

namespace Jarvis\Skill\EventBroadcaster;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
interface BroadcasterInterface
{
    const RUN_EVENT = 'jarvis.run';
    const CONTROLLER_EVENT = 'jarvis.controller';
    const RESPONSE_EVENT = 'jarvis.response';
    const EXCEPTION_EVENT = 'jarvis.exception';
    const TERMINATE_EVENT = 'jarvis.terminate';

    const RESERVED_EVENT_NAMES = [
        self::RUN_EVENT,
        self::CONTROLLER_EVENT,
        self::RESPONSE_EVENT,
        self::EXCEPTION_EVENT,
        self::TERMINATE_EVENT,
    ];

    const RECEIVER_HIGH_PRIORITY = 2;
    const RECEIVER_NORMAL_PRIORITY = 1;
    const RECEIVER_LOW_PRIORITY = 0;

    public function on(string $eventName, $receiver, int $priority = self::RECEIVER_NORMAL_PRIORITY);
    public function broadcast(string $eventName, EventInterface $event = null);
}
