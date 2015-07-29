<?php

namespace Jarvis\Annotation;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
interface AnnotationHandlerInterface
{
    public function handle($annotation);
    public function supports($annotation);
}
