<?php

namespace Jarvis;

use FastRoute\Dispatcher;
use Jarvis\Component\ControllerResolver;
use Jarvis\DependencyInjection\Container;
use Jarvis\DependencyInjection\ContainerProvider;
use Jarvis\DependencyInjection\ContainerProviderInterface;
use Jarvis\Event\AnalyzeEvent;
use Jarvis\Event\EventInterface;
use Jarvis\Event\JarvisEvents;
use Jarvis\Event\SimpleEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jarvis. Minimalist dependency injection container.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
final class Jarvis extends Container
{
    const JARVIS_CONTAINER_PROVIDER_FQCN = ContainerProvider::class;

    private $aliasOf = [];
    private $factories;
    private $hasAliases = [];
    private $locked = [];
    private $raw = [];
    private $values = [];
    private $receivers = [];
    private $scopes = [];
    private $masterEmitter = false;

    /**
     * Creates an instance of Jarvis. It can take settings as first argument.
     * List of accepted options:
     *   - container_provider (type: string|array): fqcn of your container provider
     *
     * @param  array $settings Your own settings to modify Jarvis behavior
     * @throws \InvalidArgumentException if provided container provider class doesn't
     *                                   implement ContainerProviderInterface
     */
    public function __construct(array $settings = [])
    {
        parent::__construct();

        $this['jarvis.starttime'] = microtime(true);

        if (!isset($settings['container_provider'])) {
            $settings['container_provider'] = [self::JARVIS_CONTAINER_PROVIDER_FQCN];
        } else {
            $settings = (array) $settings;
            array_unshift($settings['container_provider'], self::JARVIS_CONTAINER_PROVIDER_FQCN);
        }

        $this['jarvis.settings'] = $settings;
        foreach ($settings['container_provider'] as $classname) {
            if (!is_subclass_of($classname, ContainerProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Expect every container provider to implement %s.',
                    ContainerProviderInterface::class
                ));
            }

            $classname::hydrate($this);
        }
    }

    public function analyze()
    {
        $this->masterBroadcast(JarvisEvents::ANALYZE_EVENT, $analyzeEvent = new AnalyzeEvent($this['request']));

        if ($response = $analyzeEvent->getResponse()) {
            return $response;
        }

        $routeInfo = $this['router']->match($this['request']->getMethod(), $this['request']->getPathInfo());
        if (Dispatcher::FOUND === $routeInfo[0]) {
            $response = call_user_func_array($this['callback_resolver']->resolve($routeInfo[1]), $routeInfo[2]);
        } elseif (Dispatcher::NOT_FOUND === $routeInfo[0] || Dispatcher::METHOD_NOT_ALLOWED === $routeInfo[0]) {
            $response = new Response(null, Dispatcher::NOT_FOUND === $routeInfo[0]
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_METHOD_NOT_ALLOWED
            );
        }

        $this->masterBroadcast(JarvisEvents::RESPONSE_EVENT);

        return $response;
    }

    public function addReceiver($eventName, $receiver)
    {
        if (isset($this->receivers[$eventName])) {
            $this->receivers[$eventName] = [];
        }

        $this->receivers[$eventName][] = $receiver;

        return $this;
    }

    public function broadcast($eventName, EventInterface $event = null)
    {
        if (!$this->masterEmitter && in_array($eventName, JarvisEvents::RESERVED_EVENT_NAMES)) {
            throw new \LogicException(sprintf(
                'You\'re trying to broadcast "%s" but "%s" are reserved event names.',
                $eventName,
                implode('|', $this->reservedEventName)
            ));
        }

        if (isset($this->receivers[$eventName])) {
            $event = $event ?: new SimpleEvent();
            foreach ($this->receivers[$eventName] as $receiver) {
                call_user_func_array($this['callback_resolver']->resolve($receiver), [$event]);

                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $this;
    }

    public function disableScope($names)
    {
        foreach ((array) $names as $name) {
            unset($this->scopes[$name]);
        }

        return $this;
    }

    public function enableScope($names)
    {
        foreach ((array) $names as $name) {
            $this->scopes[$name] = true;
        }

        return $this;
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function isEnabledScope($names)
    {
        foreach ((array) $names as $name) {
            if (!isset($this->scopes[$name])) {
                return false;
            }
        }

        return true;
    }

    public function getExecutionDuration($precision = 8)
    {
        return number_format(microtime(true) - $this['jarvis.starttime'], $precision);
    }

    /**
     * Enables master emitter mode until next call of ::broadcast() method.
     *
     * @return self
     */
    private function masterBroadcast($eventName, EventInterface $event = null)
    {
        $this->masterEmitter = true;
        $this->broadcast($eventName, $event);
        $this->masterEmitter = false;

        return $this;
    }
}
