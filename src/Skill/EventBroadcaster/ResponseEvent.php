<?php

declare(strict_types = 1);

namespace Jarvis\Skill\EventBroadcaster;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ResponseEvent extends SimpleEvent
{
    protected $request;
    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return Response
     */
    public function response(): ?Response
    {
        return $this->response;
    }
}
