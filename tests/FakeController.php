<?php

namespace Jarvis\Tests;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class FakeController
{
    public function randomAction()
    {
        return new Response(self::class);
    }

    public function throwExceptionAction()
    {
        throw new \Exception(__METHOD__);
    }
}
