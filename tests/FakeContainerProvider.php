<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\ContainerProviderInterface;

class FakeContainerProvider implements ContainerProviderInterface
{
    public function hydrate(Jarvis $container)
    {
        $container['fake_container_provider_called'] = true;
        $container['is_request_already_defined'] = isset($container['request']);
    }
}
