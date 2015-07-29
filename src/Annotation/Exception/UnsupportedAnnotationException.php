<?php

namespace Jarvis\Annotation\Exception;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class UnsupportedAnnotationException extends \InvalidArgumentException
{
    public function __construct($handlerClass, $annotation)
    {
        $msg = sprintf('"%s" annotation is not supported by %s.', get_class($annotation), $handlerClass);
        parent::__construct($msg);
    }
}
