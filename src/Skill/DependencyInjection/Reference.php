<?php

declare(strict_types = 1);

namespace Jarvis\Skill\DependencyInjection;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Reference
{
    /**
     * @var string
     */
    private $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->identifier;
    }
}
