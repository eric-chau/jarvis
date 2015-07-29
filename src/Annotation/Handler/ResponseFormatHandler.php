<?php

namespace Jarvis\Annotation\Handler;

use Jarvis\Annotation\ResponseFormat;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ResponseFormatHandler extends AbstractHandler
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($annotation)
    {
        parent::handle($annotation);

        foreach ($this->request->getAcceptableContentTypes() as $acceptableContentType) {
            if (in_array($acceptableContentType, $annotation->accept)) {
                return;
            }
        }

        throw new \Exception();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($annotation)
    {
        return $annotation instanceof ResponseFormat;
    }
}
