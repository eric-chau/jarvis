<?php

namespace Jarvis\Annotation\Types;

use Minime\Annotations\Interfaces\TypeInterface;
use Minime\Annotations\ParserException;
use Minime\Annotations\Types\Json;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Concrete implements TypeInterface
{

    /**
     * Process a value to be a concrete annotation
     *
     * @param  string                              $value json string
     * @param  string                              $class name of concrete annotation type (class)
     * @throws \Minime\Annotations\ParserException
     * @return object
     */
    public function parse($value, $class = null)
    {
        if (! class_exists($class)) {
            throw new ParserException("Concrete annotation expects {$class} to exist.");
        }

        return $this->makeInstance($class, (new Json)->parse($value));
    }

    /**
     * Creates and hydrates a concrete annotation class
     *
     * @param  string   $class     full qualified class name
     * @param  stdClass $prototype object prototype
     * @return object   hydrated concrete annotation class
     */
    protected function makeInstance($class, \stdClass $prototype)
    {
        $reflection = new \ReflectionClass($class);

        return $this->doMethodConfiguration($reflection->newInstance(), $prototype);
    }

    /**
     * Do configuration injection through method calls
     *
     * @param  object   $instance  concrete annotation instance
     * @param  stdClass $prototype object prototype
     * @return object   hydrated concrete annotation class
     */
    protected function doMethodConfiguration($instance, \stdClass $prototype)
    {
        foreach ($prototype as $property => $args) {
            if (property_exists($instance, $property)) {
                $instance->$property = $args;
            }
        }

        return $instance;
    }
}
