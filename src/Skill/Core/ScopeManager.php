<?php

declare(strict_types=1);

namespace Jarvis\Skill\Core;

use Jarvis\Jarvis;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ScopeManager
{
    private $scopes = [
        Jarvis::DEFAULT_SCOPE => '',
    ];

    public function disable($names) : ScopeManager
    {
        foreach ((array) $names as $name) {
            if (Jarvis::DEFAULT_SCOPE === $name) {
                continue;
            }

            unset($this->scopes[$name]);
        }

        return $this;
    }

    public function enable($names) : ScopeManager
    {
        foreach ((array) $names as $name) {
            $this->scopes[$name] = '';
        }

        return $this;
    }

    public function getAll() : array
    {
        return array_keys($this->scopes);
    }

    public function isEnabled($names) : bool
    {
        foreach ((array) $names as $name) {
            if (!isset($this->scopes[$name])) {
                return false;
            }
        }

        return true;
    }
}
