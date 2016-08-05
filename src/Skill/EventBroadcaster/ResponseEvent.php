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

    public function __construct(Request $request, $response)
    {
        $this->request = $request;
        if (!($response instanceof Response)) {
            $response = new Response($response);
        }

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
    public function response()
    {
        return $this->response;
    }
}
