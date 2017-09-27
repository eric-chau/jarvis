<?php

declare(strict_types=1);

namespace Jarvis;

use Jarvis\Skill\Core\CallbackResolver;
use Jarvis\Skill\DependencyInjection\Container;
use Jarvis\Skill\DependencyInjection\ContainerProvider;
use Jarvis\Skill\DependencyInjection\ContainerProviderInterface;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\BroadcasterTrait;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\EventInterface;
use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use Jarvis\Skill\EventBroadcaster\ResponseEvent;
use Jarvis\Skill\EventBroadcaster\RunEvent;
use Jarvis\Skill\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jarvis. Minimalist dependency injection container.
 *
 * @property bool                                              $debug
 * @property Router                                            $router
 * @property Request                                           $request
 * @property \Symfony\Component\HttpFoundation\Session\Session $session
 * @property CallbackResolver                                  $callbackResolver
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Jarvis extends Container implements BroadcasterInterface
{
    use BroadcasterTrait {
        broadcast as traitBroadcast;
    }

    const DEFAULT_DEBUG = false;
    const CONTAINER_PROVIDER_FQCN = ContainerProvider::class;

    private $masterSetter = false;

    /**
     * Creates an instance of Jarvis. It can take settings as first argument.
     * List of accepted options:
     *   - providers (type: string|array): fqcn of your container provider
     *   - extra
     *
     * @param  array $settings Your own settings to modify Jarvis behavior
     */
    public function __construct(array $settings = [])
    {
        parent::__construct();

        $this['settings'] = $settings;
        $providers = array_merge([static::CONTAINER_PROVIDER_FQCN], (array) ($settings['providers'] ?? []));
        foreach (array_unique($providers) as $classname) {
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

        $this->masterSet($key, $this[$key]);

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
        if (!$this->masterSetter) {
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
     * @param  ContainerProviderInterface $provider
     */
    public function hydrate(ContainerProviderInterface $provider): void
    {
        $provider->hydrate($this);
    }

    /**
     * @param  Request|null $request
     * @return Response
     */
    public function run(Request $request = null): Response
    {
        $request = $request ?? $this[Request::class];
        $event = new RunEvent($request);

        try {
            $this->masterBroadcast(BroadcasterInterface::RUN_EVENT, $event);
            if ($response = $event->response()) {
                return $response;
            }

            [$callback, $arguments] = $this[Router::class]->match($request->getMethod(), $request->getPathInfo());
            $event = new ControllerEvent($this[CallbackResolver::class]->resolveReference($callback), $arguments);
            $this->masterBroadcast(BroadcasterInterface::CONTROLLER_EVENT, $event);

            $response = call_user_func_array($event->callback(), $event->arguments());
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
    public function broadcast(string $name, EventInterface $event = null): void
    {
        if (!$this->masterEmitter && in_array($name, BroadcasterInterface::RESERVED_EVENT_NAMES)) {
            throw new \LogicException(sprintf(
                'You\'re trying to broadcast "$name" but "%s" are reserved event names.',
                implode('|', BroadcasterInterface::RESERVED_EVENT_NAMES)
            ));
        }

        $this->traitBroadcast($name, $event);
    }

    /**
     * {@inheritdoc}
     */
    protected function runReceiverCallback($receiver, EventInterface $event)
    {
        $this[CallbackResolver::class]->resolveAndCall($receiver, ['event' => $event]);
    }

    /**
     * Sets new attribute into Jarvis.
     *
     * @param  string $key   The name of the new attribute
     * @param  mixed  $value The value of the new attribute
     */
    private function masterSet(string $key, $value): void
    {
        $this->masterSetter = true;
        $this->$key = $value;
        $this->masterSetter = false;
    }

    /**
     * Enables master emitter mode.
     */
    private function masterBroadcast(string $name, EventInterface $event = null): void
    {
        $this->masterEmitter = true;
        $this->broadcast($name, $event);
        $this->masterEmitter = false;
    }
}
