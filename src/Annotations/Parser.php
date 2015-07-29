<?php

namespace Jarvis\Annotations;

use Minime\Annotations\Parser as MinimeParser;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class Parser extends MinimeParser
{
    /**
     * List of Php docblock annotation to ignore
     */
    protected $annotationsToIgnore = [
        'api'            => null,
        'author'         => null,
        'category'       => null,
        'copyright'      => null,
        'deprecated'     => null,
        'example'        => null,
        'filesource'     => null,
        'global'         => null,
        'ignore'         => null,
        'internal'       => null,
        'license'        => null,
        'link'           => null,
        'method'         => null,
        'package'        => null,
        'param'          => null,
        'property'       => null,
        'property-read'  => null,
        'property-write' => null,
        'return'         => null,
        'see'            => null,
        'since'          => null,
        'source'         => null,
        'subpackage'     => null,
        'throws'         => null,
        'todo'           => null,
        'uses'           => null,
        'var'            => null,
        'version'        => null,
    ];

    /**
     * Overrides Minime\Annotation\Parser::__construct to add Jarvis custom Concrete type.
     *
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->types['Jarvis\Annotations\Types\Concrete'] = '=>';

        parent::__construct();
    }

    /**
     * Overrides Mime\Annotation\Parser::parseAnnotations to ignore Php docblock annotations.
     *
     * {@inheritdoc}
     */
    protected function parseAnnotations($str)
    {
        $annotations = [];
        preg_match_all($this->dataPattern, $str, $found);
        foreach ($found[2] as $key => $value) {
            if (isset($this->annotationsToIgnore[$found[1][$key]])) {
                continue;
            }

            $annotations[ $this->sanitizeKey($found[1][$key]) ][] = $this->parseValue($value, $found[1][$key]);
        }

        return $annotations;
    }
}
