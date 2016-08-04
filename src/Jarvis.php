<?php

declare(strict_types = 1);

namespace Jarvis;

use FastRoute\Dispatcher;
use Jarvis\Skill\DependencyInjection\Container;
use Jarvis\Skill\DependencyInjection\ContainerProvider;
use Jarvis\Skill\DependencyInjection\ContainerProviderInterface;
use Jarvis\Skill\EventBroadcaster\AnalyzeEvent;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\EventInterface;
use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use Jarvis\Skill\EventBroadcaster\PermanentEventInterface;
use Jarvis\Skill\EventBroadcaster\ResponseEvent;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jarvis. Minimalist dependency injection container.
 *
 * @property boolean $debug
 * @property \Jarvis\Skill\Routing\Router $router
 * @property \Symfony\Component\HttpFoundation\Request $request
 * @property \Symfony\Component\HttpFoundation\Session\Session $session
 * @property \Jarvis\Skill\Core\CallbackResolver $callbackResolver
 * @property \Symfony\Component\HttpFoundation\ParameterBag $settings
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

        $this['settings'] = new ParameterBag($settings);
        $this->lock('settings');

        $this['debug'] = $this->settings->getBoolean('debug', static::DEFAULT_DEBUG);
        $this->lock('debug');

        if (!$this->settings->has('container_provider')) {
            $this->settings->set('container_provider', [static::CONTAINER_PROVIDER_FQCN]);
        } else {
            $containerProvider = (array) $this->settings->get('container_provider');
            array_unshift($containerProvider, static::CONTAINER_PROVIDER_FQCN);
            $this->settings->set('container_provider', $containerProvider);
        }

        foreach ($this->settings->get('container_provider') as $classname) {
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
     * @param  Request|null $request
     * @return Response
     */
    public function analyze(Request $request = null): Response
    {
        $request = $request ?? $this->request;
        $response = null;

        try {
            $this->masterBroadcast(BroadcasterInterface::ANALYZE_EVENT, $analyzeEvent = new AnalyzeEvent($request));

            if ($response = $analyzeEvent->response()) {
                return $response;
            }

            $routeInfo = $this->router->match($request->getMethod(), $request->getPathInfo());
            if (Dispatcher::FOUND === $routeInfo[0]) {
                $callback = $this->callbackResolver->resolve($routeInfo[1]);

                $event = new ControllerEvent($callback, $routeInfo[2]);
                $this->masterBroadcast(BroadcasterInterface::CONTROLLER_EVENT, $event);

                $response = call_user_func_array($event->callback(), $event->arguments());

                if (is_scalar($response)) {
                    $response = new Response((string) $response);
                }
            } elseif (Dispatcher::NOT_FOUND === $routeInfo[0] || Dispatcher::METHOD_NOT_ALLOWED === $routeInfo[0]) {
                $response = new Response(null, Dispatcher::NOT_FOUND === $routeInfo[0]
                    ? Response::HTTP_NOT_FOUND
                    : Response::HTTP_METHOD_NOT_ALLOWED
                );
            }

            $this->masterBroadcast(BroadcasterInterface::RESPONSE_EVENT, new ResponseEvent($request, $response));
        } catch (\Throwable $throwable) {
            $exceptionEvent = new ExceptionEvent($throwable);
            $this->masterBroadcast(BroadcasterInterface::EXCEPTION_EVENT, $exceptionEvent);
            $response = $exceptionEvent->response();
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function on(string $eventName, $receiver, int $priority = BroadcasterInterface::RECEIVER_NORMAL_PRIORITY)
    {
        if (!isset($this->receivers[$eventName])) {
            $this->receivers[$eventName] = [
                BroadcasterInterface::RECEIVER_LOW_PRIORITY    => [],
                BroadcasterInterface::RECEIVER_NORMAL_PRIORITY => [],
                BroadcasterInterface::RECEIVER_HIGH_PRIORITY   => [],
            ];
        }

        $this->receivers[$eventName][$priority][] = $receiver;
        $this->computedReceivers[$eventName] = null;

        if (isset($this->permanentEvents[$eventName])) {
            $event = $this->permanentEvents[$eventName];

            call_user_func_array($this->callbackResolver->resolve($receiver), [$event]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(string $eventName, EventInterface $event = null)
    {
        if (!$this->masterEmitter && in_array($eventName, BroadcasterInterface::RESERVED_EVENT_NAMES)) {
            throw new \LogicException(sprintf(
                'You\'re trying to broadcast "$eventName" but "%s" are reserved event names.',
                implode('|', BroadcasterInterface::RESERVED_EVENT_NAMES)
            ));
        }

        if (isset($this->receivers[$eventName])) {
            $event = $event ?? new SimpleEvent();
            if ($event instanceof PermanentEventInterface && $event->isPermanent()) {
                $this->permanentEvents[$eventName] = $event;
            }

            foreach ($this->buildEventReceivers($eventName) as $receiver) {
                call_user_func_array($this->callbackResolver->resolve($receiver), [$event]);

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
    private function masterBroadcast(string $eventName, EventInterface $event = null): Jarvis
    {
        $this->masterEmitter = true;
        $this->broadcast($eventName, $event);
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
     * @param  string $eventName The event name we want to get its receivers
     * @return array
     */
    private function buildEventReceivers(string $eventName): array
    {
        return $this->computedReceivers[$eventName] = $this->computedReceivers[$eventName] ?? array_merge(
            $this->receivers[$eventName][BroadcasterInterface::RECEIVER_HIGH_PRIORITY],
            $this->receivers[$eventName][BroadcasterInterface::RECEIVER_NORMAL_PRIORITY],
            $this->receivers[$eventName][BroadcasterInterface::RECEIVER_LOW_PRIORITY]
        );
    }
}
