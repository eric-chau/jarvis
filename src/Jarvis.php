<?php

namespace Jarvis;

use Jarvis\Component\ControllerResolver;
use Jarvis\ContainerProviderInterface;

/**
 * Jarvis. Minimalist dependency injection container.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
final class Jarvis implements \ArrayAccess
{
    const JARVIS_CONTAINER_PROVIDER_FQCN = 'Jarvis\ContainerProvider';

    private $aliasOf = [];
    private $factories;
    private $hasAliases = [];
    private $locked = [];
    private $raw = [];
    private $values = [];

    /**
     * Creates an instance of Jarvis. It can take settings as first argument.
     * List of accepted options:
     *   - jarvis.container_provider (type: string|array): fqcn of your container provider
     *
     * @param  array $settings Your own settings to modify Jarvis behavior
     * @throws \InvalidArgumentException if provided container provider class doesn't
     *                                   implement ContainerProviderInterface
     */
    public function __construct(array $settings = [])
    {
        $this['jarvis.starttime'] = microtime(true);
        $this->factories = new \SplObjectStorage();

        if (!isset($settings['jarvis.container_provider'])) {
            $settings['jarvis.container_provider'] = [self::JARVIS_CONTAINER_PROVIDER_FQCN];
        } else {
            $settings = (array) $settings;
            array_unshift($settings, self::JARVIS_CONTAINER_PROVIDER_FQCN);
        }

        $this['jarvis.settings'] = $settings;
        foreach ($settings['jarvis.container_provider'] as $classname) {
            if (!is_subclass_of($classname, ContainerProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Expect every container provider to implement %s.',
                    ContainerProviderInterface::class
                ));
            }

            $classname::hydrate($this);
        }
    }

    public function broadcast($event)
    {

    }

    public function getExecutionDuration($precision = 8)
    {
        return number_format(microtime(true) - $this['jarvis.starttime'], $precision);
    }

    /**
     * Adds alias to service's identifier.
     *
     * @param  string $alias the alias to identifier
     * @param  string $id    the identifier to alias
     * @return self
     * @throws InvalidArgumentException if provided identifier is undefined or if alias is
     *                                  equals to identifier
     */
    public function alias($alias, $id)
    {
        if (!$this->offsetExists($id)) {
            throw new \InvalidArgumentException("Cannot create alias for undefined value `$id`.");
        }

        if ($alias === $id || array_key_exists($alias, $this->values)) {
            throw new \InvalidArgumentException('Alias cannot be equals to value identifier.');
        }

        $this->aliasOf[$alias] = $id;
        if (!isset($this->hasAliases[$id])) {
            $this->hasAliases[$id] = [];
        }

        $this->hasAliases[$id][] = $alias;

        return $this;
    }

    /**
     * Retrieves parameter and/or object by identifier.
     * this method also support wildcard character (*) in identifier.
     *
     * @param  string $id this identifier can contain one or many wildcard character (*)
     * @return array an array of values that mached with provided identifier pattern
     */
    public function find($id)
    {
        $values = [];
        $pattern = str_replace(['.', '*'], ['\.', '[\w\-\.]*'], $id);
        foreach ($this->keys() as $id) {
            if (1 === preg_match('/^'.$pattern.'$/', $id)) {
                $values[] = $this->offsetGet($id);
            }
        }

        return $values;
    }

    /**
     * Checks if a parameter or an object is defined.
     *
     * @param  string $id the parameter/object identifier to check
     * @return boolean
     */
    public function offsetExists($id)
    {
        return in_array($id, $this->keys());
    }

    /**
     * Gets a parameter or an object.
     *
     * @param  string $id the parameter/object identifier
     * @return mixed The requested value
     * @throws \InvalidArgumentException if provided identifier is not defined
     */
    public function offsetGet($id)
    {
        $this->throwExceptionIfIdentifierNotFound($id);
        $id = $this->resolveIdentifier($id);

        if (
            isset($this->raw[$id])
            || !is_object($this->values[$id])
            || !method_exists($this->values[$id], '__invoke')
        ) {
            return $this->values[$id];
        }

        if (isset($this->factories[$this->values[$id]])) {
            return $this->values[$id]($this);
        }

        $this->raw[$id] = $this->values[$id];

        return $this->values[$id] = $this->raw[$id]($this);
    }

    /**
     * Sets a parameter or an object.
     *
     * Note that you cannot override locked value, you have to call ::offsetUnset first.
     *
     * @param  string $id the identifier for parameter or object
     * @param  mixed  $v  the value of the parameter or an object
     * @throws \RuntimeException prevents override of locked value
     * @throws \InvalidArgumentException prevents value's identifier to be equal to an existing alias
     */
    public function offsetSet($id, $v)
    {
        if (isset($this->locked[$id])) {
            throw new \RuntimeException("Cannot override locked value '$id'");
        }

        if (isset($this->aliasOf[$id])) {
            throw new \InvalidArgumentException('Value\'s identifier cannot be equal to existing alias.');
        }

        $this->values[$id] = $v;
    }

    /**
     * Unsets a parameter or an object. It can also unset an alias.
     *
     * Note that if you unset a value it will also unset all its aliases.
     *
     * @param  string $id the identifier of the object/parameter to unset
     */
    public function offsetUnset($id)
    {
        if (isset($this->values[$id])) {
            if (is_object($this->values[$id])) {
                unset($this->factories[$this->values[$id]]);
            }

            if (isset($this->hasAliases[$id])) {
                foreach ($this->hasAliases[$id] as $alias) {
                    unset($this->aliasOf[$alias]);
                }
            }

            unset($this->values[$id], $this->raw[$id], $this->hasAliases[$id], $this->locked[$id]);
        } else {
            unset($this->aliasOf[$id]);
        }
    }

    /**
     * Returns all defined identifiers and aliases.
     *
     * @return array
     */
    public function keys()
    {
        return array_merge(array_keys($this->values), array_keys($this->aliasOf));
    }

    /**
     * Add provided service as factory.
     *
     * @param  string $id      the factory identifier
     * @param  mixed  $factory the factory object
     * @return self
     * @throws InvalidArgumentException if provided factory is not a Closure or not an invokable object
     */
    public function factory($id, $factory)
    {
        if (!is_object($factory) || !method_exists($factory, '__invoke')) {
            throw new \InvalidArgumentException('Service factory must be a Closure or an invokable object.');
        }

        $this->offsetSet($id, $factory);
        $this->factories->attach($factory);

        return $this;
    }

    /**
     * Locks an object or a parameter so you can not override it until unset() is called.
     *
     * @param  string $id the identifier to lock
     * @return self
     */
    public function lock($id)
    {
        $this->throwExceptionIfIdentifierNotFound($id);
        $id = $this->resolveIdentifier($id);
        $this->locked[$id] = true;

        return $this;
    }

    /**
     * Checks if provided identifier exists and throws exception if not.
     *
     * @param  string $id the identifier we want to know if it exists
     * @throws \InvalidArgumentException if provided identifier is not defined
     */
    private function throwExceptionIfIdentifierNotFound($id)
    {
        if (!$this->offsetExists($id)) {
            throw new \InvalidArgumentException("Identifier `$id` is not defined.");
        }
    }

    /**
     * Returns associated identifier if provided argument is an alias.
     *
     * @param  string $id the identifier to convert if needed
     * @return string
     */
    private function resolveIdentifier($id)
    {
        if (isset($this->values[$id])) {
            return $id;
        }

        return $this->aliasOf[$id];
    }
}
