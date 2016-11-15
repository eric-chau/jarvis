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
    private $app;

    public function __construct(Jarvis $app)
    {
        $this->app = $app;
    }

    public function resolve($callback): \Closure
    {
        if (is_array($callback) && $callback[0] instanceof Reference) {
            $callback[0] = $this->app[(string) $callback[0]] ?? $callback[0];
        }

        return \Closure::fromCallable($callback);
    }
}
