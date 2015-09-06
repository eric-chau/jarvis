<?php

namespace Jarvis\Skill\DependencyInjection;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Reference
{
    private $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}
