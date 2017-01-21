<?php

declare(strict_types=1);

namespace Jarvis;

use FastRoute\Dispatcher;
use Jarvis\Skill\DependencyInjection\Container;
use Jarvis\Skill\DependencyInjection\ContainerProvider;
use Jarvis\Skill\DependencyInjection\ContainerProviderInterface;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\EventInterface;
use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use Jarvis\Skill\EventBroadcaster\PermanentEventInterface;
use Jarvis\Skill\EventBroadcaster\ResponseEvent;
use Jarvis\Skill\EventBroadcaster\RunEvent;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jarvis. Minimalist dependency injection container.
 *
 * @property boolean $debug
 * @property Request $request
 * @property \Jarvis\Skill\Routing\Router $router
 * @property \Symfony\Component\HttpFoundation\Session\Session $session
 * @property \Jarvis\Skill\Core\CallbackResolver $callbackResolver
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Jarvis extends Container implements BroadcasterInterface
{
    const DEFAULT_DEBUG = false;
    const CONTAINER_PROVIDER_FQCN = ContainerProvider::class;

    private $receivers = [];
    private $permanentEvents = [];
    private $computedReceivers = [];
    private $masterEmitter = false;
    private $masterSet = false;

    /**
     * Creates an instance of Jarvis. It can take settings as first argument.
     * List of accepted options:
     *   - container_provider (type: string|array): fqcn of your container provider
     *
     * @param  array $settings Your own settings to modify Jarvis behavior
     */
    public function __construct(array $settings = [])
    {
        parent::__construct();

        $this['settings'] = $settings;
        $providers = array_merge([static::CONTAINER_PROVIDER_FQCN], (array) ($settings['providers'] ?? []));
        foreach ($providers as $classname) {
            $this->hydrate(new $classname());
        }
    }

    public function __destruct()
    {
        $this->masterBroadcast(BroadcasterInterface::TERMINATE_EVENT);
    }

    /**
     * This method is an another way to get a locked value.
     *
     * Example: $this['foo'] is equal to $this->foo, but it ONLY works for locked values.
     *
     * @param  string $key The key of the locked value
     * @return mixed
     * @throws \InvalidArgumentException if the requested key is not associated to a locked service
     */
    public function __get(string $key)
    {
        if (!isset($this->locked[$key])) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a key of a locked value.', $key));
        }

        $this->masterSetter($key, $this[$key]);

        return $this->$key;
    }

    /**
     * Sets new attributes to Jarvis. Note that this method is reserved to Jarvis itself only.
     *
     * @param string $key   The key name of the new attribute
     * @param mixed  $value The value to associate to provided key
     * @throws \LogicException if this method is not called by Jarvis itself
     */
    public function __set(string $key, $value)
    {
        if (!$this->masterSet) {
            throw new \LogicException('You are not allowed to set new attribute into Jarvis.');
        }

        $this->$key = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($id, $v): void
    {
        parent::offsetSet($id, $v);

        if (!($v instanceof \Closure)) {
            return;
        }

        $refMethod = new \ReflectionMethod($v, '__invoke');
        if (null === $returntype = $refMethod->getReturnType()) {
            return;
        }

        $alias = $returntype->getName();
        if (
            $alias === $id
            || (!class_exists($alias) && !interface_exists($alias))
        ) {
            return;
        }

        if (!isset($this[$alias])) {
            $this->alias($alias, $id);
        } else {
            unset($this[$alias]);
        }
    }

    /**
     * @param  Request|null $request
     * @return Response
     */
    public function run(Request $request = null): Response
    {
        $request = $request ?? $this['request'];
        $event = null;

        try {
            $this->masterBroadcast(BroadcasterInterface::RUN_EVENT, $event = new RunEvent($request));
            if ($response = $event->response()) {
                return $response;
            }

            [$callback, $arguments] = $this['router']->match($request->getMethod(), $request->getPathInfo());
            $event = new ControllerEvent($this['callbackResolver']->resolve($callback), $arguments);
            $this->masterBroadcast(BroadcasterInterface::CONTROLLER_EVENT, $event);

            $response = $this['callbackResolver']->resolveAndCall($event->callback(), $event->arguments());
            $event = new ResponseEvent($request, $response);
            $this->masterBroadcast(BroadcasterInterface::RESPONSE_EVENT, $event);
        } catch (\Throwable $throwable) {
            $event = new ExceptionEvent($throwable);
            $this->masterBroadcast(BroadcasterInterface::EXCEPTION_EVENT, $event);
        }

        return $event->response();
    }

    /**
     * {@inheritdoc}
     */
    public function on(string $name, $receiver, int $priority = BroadcasterInterface::RECEIVER_NORMAL_PRIORITY): Jarvis
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
            $this['callbackResolver']->resolveAndCall($receiver, [
                'event' => $this->permanentEvents[$name],
            ]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(string $name, EventInterface $event = null): Jarvis
    {
        if (!$this->masterEmitter && in_array($name, BroadcasterInterface::RESERVED_EVENT_NAMES)) {
            throw new \LogicException(sprintf(
                'You\'re trying to broadcast "$name" but "%s" are reserved event names.',
                implode('|', BroadcasterInterface::RESERVED_EVENT_NAMES)
            ));
        }

        if (isset($this->permanentEvents[$name])) {
            throw new \LogicException('Permanent event cannot be broadcasted multiple times.');
        }

        $event = $event ?? new SimpleEvent();
        if ($event instanceof PermanentEventInterface && $event->isPermanent()) {
            $this->permanentEvents[$name] = $event;
        }

        if (isset($this->receivers[$name])) {
            foreach ($this->buildEventReceivers($name) as $receiver) {
                $this['callbackResolver']->resolveAndCall($receiver, [
                    'event' => $event,
                ]);
                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @param  ContainerProviderInterface $provider
     * @return self
     */
    public function hydrate(ContainerProviderInterface $provider): Jarvis
    {
        $provider->hydrate($this);

        return $this;
    }

    /**
     * Enables master emitter mode.
     *
     * @return self
     */
    private function masterBroadcast(string $name, EventInterface $event = null): Jarvis
    {
        $this->masterEmitter = true;
        $this->broadcast($name, $event);
        $this->masterEmitter = false;

        return $this;
    }

    /**
     * Sets new attribute into Jarvis.
     *
     * @param  string $key   The name of the new attribute
     * @param  mixed  $value The value of the new attribute
     * @return self
     */
    private function masterSetter(string $key, $value): Jarvis
    {
        $this->masterSet = true;
        $this->$key = $value;
        $this->masterSet = false;

        return $this;
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
