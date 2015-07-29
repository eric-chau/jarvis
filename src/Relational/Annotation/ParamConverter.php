<?php

namespace Jarvis\Relational\Annotation;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ParamConverter
{
    const DEFAULT_IDENTIFIER_SOURCE = 'attributes';
    const REQUEST_SOURCE = 'request';
    const QUERY_SOURCE = 'request';
    const ACCEPTED_IDENTIFIER_SOURCE = [
        self::DEFAULT_IDENTIFIER_SOURCE,
        self::REQUEST_SOURCE,
        self::QUERY_SOURCE,
    ];

    const DEFAULT_IDENTIFIER_NAME = 'id';

    public $entity_name;
    public $id_source = self::DEFAULT_IDENTIFIER_SOURCE;
    public $id_name = self::DEFAULT_IDENTIFIER_NAME;
    public $name;
    public $required = true;
}
