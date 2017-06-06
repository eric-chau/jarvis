<?php

declare(strict_types=1);

namespace Jarvis\Skill\EventBroadcaster;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
trait BroadcasterTrait
{
    private $receivers = [];
    private $permanentEvents = [];
    private $computedReceivers = [];
    private $masterEmitter = false;

    /**
     * {@inheritdoc}
     */
    public function on(string $name, $receiver, int $priority = BroadcasterInterface::RECEIVER_NORMAL_PRIORITY): void
    {
        if (!isset($this->receivers[$name])) {
            $this->receivers[$name] = [
                BroadcasterInterface::RECEIVER_LOW_PRIORITY    => [],
                BroadcasterInterface::RECEIVER_NORMAL_PRIORITY => [],
                BroadcasterInterface::RECEIVER_HIGH_PRIORITY   => [],
            ];
        }

        $this->receivers[$name][$priority][] = $receiver;
        $this->computedReceivers[$name] = null;
        if (isset($this->permanentEvents[$name])) {
            $this->runReceiverCallback($receiver, $this->permanentEvents[$name]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(string $name, EventInterface $event = null): void
    {
        if (isset($this->permanentEvents[$name])) {
            throw new \LogicException('Permanent event cannot be broadcasted multiple times.');
        }

        $event = $event ?? new SimpleEvent();
        if ($event instanceof PermanentEventInterface && $event->isPermanent()) {
            $this->permanentEvents[$name] = $event;
        }

        if (isset($this->receivers[$name])) {
            foreach ($this->buildEventReceivers($name) as $receiver) {
                $this->runReceiverCallback($receiver, $event);
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function runReceiverCallback($receiver, EventInterface $event)
    {
        call_user_func($receiver, ['event' => $event]);
    }

    /**
     * Builds and returns well ordered receivers collection that match with provided event name.
     *
     * @param  string $name The event name we want to get its receivers
     * @return array
     */
    private function buildEventReceivers(string $name): array
    {
        return $this->computedReceivers[$name] = $this->computedReceivers[$name] ?? array_merge(
            $this->receivers[$name][BroadcasterInterface::RECEIVER_HIGH_PRIORITY],
            $this->receivers[$name][BroadcasterInterface::RECEIVER_NORMAL_PRIORITY],
            $this->receivers[$name][BroadcasterInterface::RECEIVER_LOW_PRIORITY]
        );
    }
}
