<?php

namespace Jarvis\Annotations;

use Minime\Annotations\Parser as MinimeParser;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Parser extends MinimeParser
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->types['Jarvis\Annotations\Types\Concrete'] = '=>';

        parent::__construct();
    }
}
