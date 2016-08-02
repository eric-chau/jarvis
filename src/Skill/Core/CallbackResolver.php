<?php

declare(strict_types = 1);

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
    private $jarvis;

    public function __construct(Jarvis $jarvis)
    {
        $this->jarvis = $jarvis;
    }

    public function resolve($callback): \Closure
    {
        if (is_array($callback) && $callback[0] instanceof Reference) {
            $callback[0] = $this->jarvis[(string) $callback[0]] ?? $callback[0];
        }

        return \Closure::fromCallable($callback);
    }
}
