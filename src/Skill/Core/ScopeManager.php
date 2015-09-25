<?php

namespace Jarvis\Skill\Core;

use Jarvis\Jarvis;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ScopeManager
{
    private $scopes = [
        Jarvis::DEFAULT_SCOPE => true,
    ];

    public function disable($names)
    {
        foreach ((array) $names as $name) {
            if (Jarvis::DEFAULT_SCOPE === $name) {
                continue;
            }

            unset($this->scopes[$name]);
        }

        return $this;
    }

    public function enable($names)
    {
        foreach ((array) $names as $name) {
            $this->scopes[$name] = true;
        }

        return $this;
    }

    public function getAll()
    {
        return array_keys($this->scopes);
    }

    public function isEnabled($names)
    {
        foreach ((array) $names as $name) {
            if (!isset($this->scopes[$name])) {
                return false;
            }
        }

        return true;
    }
}
