<?php

namespace Jarvis\Skill\DependencyInjection;

use Jarvis\Jarvis;

/**
 * This interface allows you to hydrate Jarvis' container with your own services
 * and parameters.
 *
 * @author Eric Chau <eric.chau@gmail.com>
 */
interface ContainerProviderInterface
{
    /**
     * Hydrates provided Jarvis container.
     *
     * @param  Jarvis $container The container to hydrate
     */
    public static function hydrate(Jarvis $container);
}
