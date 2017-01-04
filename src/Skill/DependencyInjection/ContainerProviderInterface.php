<?php

declare(strict_types=1);

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
     * @param  Jarvis $app The container to hydrate
     */
    public function hydrate(Jarvis $app);
}
