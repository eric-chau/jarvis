<?php

namespace Jarvis\EventBroadcaster;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class AnalyzeEvent extends SimpleEvent
{
    private $request;
    private $response;

    /**
     * Creates an instance of AnalyzeEvent.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param  Response $response
     * @return self
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }
}
