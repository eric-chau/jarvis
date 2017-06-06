<?php

declare(strict_types=1);

namespace Jarvis\Skill\DependencyInjection;

/**
 * Minimalist dependency injection container
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Container implements \ArrayAccess
{
    protected $locked = [];
    private $aliasOf = [];
    private $factories;
    private $hasAliases = [];
    private $raw = [];
    private $values = [];

    public function __construct()
    {
        $this->factories = new \SplObjectStorage();
    }

    /**
     * Checks if a parameter or an object is defined.
     *
     * @param  string $id the parameter/object identifier to check
     * @return boolean
     */
    public function offsetExists($id): bool
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
    public function offsetSet($id, $v): void
    {
        if (isset($this->locked[$id])) {
            throw new \RuntimeException(sprintf('Cannot override locked value "%s".', $id));
        }

        if (isset($this->aliasOf[$id])) {
            throw new \InvalidArgumentException("Value's identifier cannot be equal to existing alias.");
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
    public function offsetUnset($id): void
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
     * Adds alias to service's identifier.
     *
     * @param  string $alias the alias to identifier
     * @param  string $id    the identifier to alias
     * @return self
     * @throws InvalidArgumentException if provided identifier is undefined or if alias is
     *                                  equals to identifier
     */
    public function alias(string $alias, string $id): void
    {
        if (!$this->offsetExists($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot create alias for undefined value "%s".', $id));
        }

        if ($alias === $id || array_key_exists($alias, $this->values)) {
            throw new \InvalidArgumentException('Alias cannot be equals to value identifier.');
        }

        $this->aliasOf[$alias] = $id;
        $this->hasAliases[$id] = $this->hasAliases[$id] ?? [];
        $this->hasAliases[$id][] = $alias;
    }

    /**
     * Retrieves parameter and/or object by identifier.
     * this method also support wildcard character (*) in identifier.
     *
     * @param  string $id this identifier can contain one or many wildcard character (*)
     * @return array an array of values that mached with provided identifier pattern
     */
    public function find(string $id): array
    {
        $values = [];
        $pattern = str_replace(['.', '*'], ['\.', '[\w\-\.]*'], $id);
        foreach ($this->keys() as $id) {
            if (1 === preg_match(sprintf('/^%s$/', $pattern), $id)) {
                $values[] = $this->offsetGet($id);
            }
        }

        return $values;
    }

    /**
     * Returns all defined identifiers and aliases.
     *
     * @return array
     */
    public function keys(): array
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
    public function factory(string $id, $factory): void
    {
        if (!is_object($factory) || !method_exists($factory, '__invoke')) {
            throw new \InvalidArgumentException('Service factory must be a closure or an invokable object.');
        }

        $this->offsetSet($id, $factory);
        $this->factories->attach($factory);
    }

    /**
     * Locks an object or a parameter so you can not override it until unset() is called.
     *
     * @param  string|array $ids the identifier(s) to lock
     * @return self
     */
    public function lock($ids): void
    {
        foreach ((array) $ids as $id) {
            $this->locked[$this->resolveIdentifier($id)] = true;
        }
    }

    /**
     * Returns associated identifier if provided argument is an alias.
     *
     * @param  string $id the identifier to convert if needed
     * @return string
     * @throws \InvalidArgumentException if provided identifier is not defined
     */
    private function resolveIdentifier(string $id): string
    {
        if (!$this->offsetExists($id)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->aliasOf[$id] ?? $id;
    }
}
