<?php

namespace Jarvis\Relational\Annotation\Handler;

use Jarvis\Annotation\Handler\AbstractHandler;
use Jarvis\Relational\Annotation\ParamConverter;
use Respect\Relational\Mapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ParamConverterHandler extends AbstractHandler
{
    private $request;
    private $mapper;

    public function __construct(Request $request, Mapper $mapper)
    {
        $this->request = $request;
        $this->mapper = $mapper;
    }

    public function handle($annotation)
    {
        parent::handle($annotation);

        $entityName = $annotation->entity_name;
        $source = $annotation->id_source;
        $idName = $annotation->id_name;

        $entity = $this->mapper->$entityName($this->request->$source->get($idName, ''))->fetch();
        if (false === $entity && true === $annotation->required) {
            throw new \Exception('entity not found');
        }

        if (false !== $entity) {
            $this->request->attributes->set($annotation->name, $entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($annotation)
    {
        return $annotation instanceof ParamConverter;
    }
}
