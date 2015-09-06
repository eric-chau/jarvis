<?php

namespace Jarvis\Skill\EventBroadcaster;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ExceptionEvent extends SimpleEvent
{
    private $exception;
    private $response;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
        $this->stopPropagation();

        return $this;
    }

    /**
     * Forbids stop of current event propagation if response is not setted.
     *
     * {@inheritdoc}
     */
    public function stopPropagation()
    {
        if (null === $this->response) {
            return $this;
        }

        return parent::stopPropagation();
    }
}
