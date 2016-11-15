<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\ContainerProviderInterface;

class FakeContainerProvider implements ContainerProviderInterface
{
    public function hydrate(Jarvis $app)
    {
        $app['fake_container_provider_called'] = true;
        $app['is_request_already_defined'] = isset($app['request']);
    }
}
