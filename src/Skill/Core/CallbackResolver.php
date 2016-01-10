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
    private $jarvis;

    public function __construct(Jarvis $jarvis)
    {
        $this->jarvis = $jarvis;
    }

    public function resolve($callback)
    {
        if (is_array($callback) && $callback[0] instanceof Reference) {
            if (isset($this->jarvis[$callback[0]->identifier()])) {
                $callback[0] = $this->jarvis[$callback[0]->identifier()];
            }
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Provided callback is not callable.');
        }

        return $callback;
    }
}
