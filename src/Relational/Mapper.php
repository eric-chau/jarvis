<?php

namespace Jarvis\Relational;

use Respect\Relational\Mapper as RespectMapper;

class Mapper extends RespectMapper
{
    /**
     * {@inheritdoc}
     */
    protected function inferSet(&$entity, $prop, $value)
    {
        foreach ($this->generatePossiblePropertyNames($prop) as $property) {
            if (property_exists($entity, $property)) {
                $mirror = new \ReflectionProperty($entity, $property);
                $mirror->setAccessible(true);
                $mirror->setValue($entity, $value);

                return;
            }
        }

        $entity->{$prop} = $value;
    }

    /**
     * {@inheritdoc}
     */
    // protected function postHydrate(\SplObjectStorage $entities)
    // {
    //     $entitiesClone = clone $entities;

    //     foreach ($entities as $instance) {
    //         foreach ($this->getAllProperties($instance) as $field => $v) {
    //             // var_dump($field); // use annotation!!
    //             if (!$this->getStyle()->isRemoteIdentifier($field)) {
    //                 continue;
    //             }

    //             foreach ($entitiesClone as $sub) {
    //                 $this->tryHydration($entities, $sub, $field, $v);
    //             }
    //             $this->inferSet($instance, $field, $v);
    //         }
    //     }
    // }

    private function generatePossiblePropertyNames($prop)
    {
        $possibilities = [$prop];
        if (1 === preg_match('/([\w]+)_id/i', $prop, $matches)) {
            array_unshift($possibilities, $matches[1]);
        }

        $result = [];
        foreach ($possibilities as $possibility) {
            $pieces = explode('_', $possibility);
            $result[] = implode('', array_merge((array) array_shift($pieces), array_map(function ($name) {
                return ucfirst($name);
            }, $pieces)));
        }

        $result[] = $prop;

        return $result;
    }
}
