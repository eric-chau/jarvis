<?php

namespace Jarvis\Annotation;

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
        'api'            => true,
        'author'         => true,
        'category'       => true,
        'copyright'      => true,
        'deprecated'     => true,
        'example'        => true,
        'filesource'     => true,
        'global'         => true,
        'ignore'         => true,
        'internal'       => true,
        'license'        => true,
        'link'           => true,
        'method'         => true,
        'package'        => true,
        'param'          => true,
        'property'       => true,
        'property-read'  => true,
        'property-write' => true,
        'return'         => true,
        'see'            => true,
        'since'          => true,
        'source'         => true,
        'subpackage'     => true,
        'throws'         => true,
        'todo'           => true,
        'uses'           => true,
        'var'            => true,
        'version'        => true,
    ];

    /**
     * Overrides Minime\Annotation\Parser::__construct to add Jarvis custom Concrete type.
     *
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->types['Jarvis\Annotation\Types\Concrete'] = '=>';

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
