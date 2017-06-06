<?php

declare(strict_types=1);

namespace Jarvis\Skill\Core;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\Reference;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class CallbackResolver
{
    /**
     * @var Jarvis
     */
    private $app;

    public function __construct(Jarvis $app)
    {
        $this->app = $app;
    }

    /**
     * Resolves and replaces Reference by the parameter/service from Jarvis's
     * dependency injection container.
     *
     * @param  mixed $callback
     * @return mixed
     */
    public function resolveReference($callback)
    {
        if ($callback instanceof Reference) {
            $callback = $this->app[(string) $callback] ?? $callback;
        } elseif (is_array($callback) && $callback[0] instanceof Reference) {
            $callback[0] = $this->app[(string) $callback[0]] ?? $callback[0];
        }

        return $callback;
    }

    /**
     * Resolves and replaces placeholders references by the parameter from Jarvis's
     * dependency injection container.
     *
     * @param  mixed $callback
     * @return \Closure
     */
    public function toClosure($callback): \Closure
    {
        if ($callback instanceof \Closure) {
            return $callback;
        }

        return \Closure::fromCallable($this->resolveReference($callback));
    }

    /**
     * Resolves and returns an array of arguments according to the given closure.
     * This method can also smartly type hint and find the right object to match
     * callback requested arguments.
     *
     * @param  \Closure $callback
     * @param  array    $rawArgs
     * @return array
     */
    public function resolveArgumentsForClosure(\Closure $callback, array $rawArgs = []): array
    {
        $result = [];
        $refMethod = new \ReflectionMethod($callback, '__invoke');
        foreach ($refMethod->getParameters() as $refParam) {
            if (null !== $refClass = $refParam->getClass()) {
                if (isset($this->app[$refClass->name])) {
                    $result[$refParam->getPosition()] = $this->app[$refClass->name];

                    continue;
                }
            }

            if (in_array($refParam->name, array_keys($rawArgs))) {
                $result[$refParam->getPosition()] = $rawArgs[$refParam->name];
            }
        }

        return $result;
    }

    /**
     * Shortcut that calls successively ::toClosure(), ::resolveArgumentsForClosure(),
     * call_user_func_array() and returns the result.
     *
     * @param  mixed $callback
     * @param  array  $rawArgs
     * @return mixed
     */
    public function resolveAndCall($callback, array $rawArgs = [])
    {
        $closure = $this->toClosure($callback);

        return call_user_func_array(
            $closure,
            $this->resolveArgumentsForClosure($closure, $rawArgs)
        );
    }
}
