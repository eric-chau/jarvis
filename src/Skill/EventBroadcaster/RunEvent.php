<?php

declare(strict_types=1);

namespace Jarvis\Skill\EventBroadcaster;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RunEvent extends ResponseEvent
{
    /**
     * Creates an instance of RunEvent.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param  Response $response
     * @return self
     */
    public function setResponse(Response $response): RunEvent
    {
        $this->response = $response;

        return $this;
    }
}
