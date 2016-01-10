<?php

declare(strict_types=1);

namespace Jarvis\Skill\DependencyInjection;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Reference
{
    private $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function identifier() : string
    {
        return $this->identifier;
    }
}
