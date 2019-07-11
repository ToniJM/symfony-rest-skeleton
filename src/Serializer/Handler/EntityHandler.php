<?php


namespace App\Serializer\Handler;

use App\Annotation\DeserializeEntity;
use App\Entity\Date;
use App\Entity\Persona;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use LogicException;
use ReflectionObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityHandler implements SubscribingHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(EntityManagerInterface $manager, Reader $reader)
    {
        $this->manager = $manager;
        $this->reader = $reader;
    }

    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        $types = [
            Date::class,
            Persona::class
        ];

        $array = [];

        for ($i = 0; $i < count($types); $i++) {
            $array[] = [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => $types[$i],
                'method' => 'deserializeEntity'
            ];
        }
        return $array;
    }

    public function deserializeEntity(JsonDeserializationVisitor $visitor, $data, array $type, Context $context)
    {
        if ($context->getDepth() > 1) {
            if (!is_numeric($data) && !isset($data['id'])) {
                throw new BadRequestHttpException('Subresources needs an Id');
            }

            $id = is_numeric($data) ? $data : $data['id'];
            $entity = $this->manager->find($type['name'], $id);

            if (null === $entity) {
                throw new NotFoundHttpException("Subresource {$type['name']}/$id");
            }
            return $entity;
        } else {
            $entity = new $type['name']();
            $reflection = new ReflectionObject($entity);
            
            // recorro las propiedades
            foreach ($reflection->getProperties() as $property) {
                if (!isset($data[$property->getName()])) {

                    continue;
                }

                $idAnnotation = $this->reader->getPropertyAnnotation($property, Id::class);
                if (null !== $idAnnotation) {
                    continue;
                }

                $columnAnnotation = $this->reader->getPropertyAnnotation($property, Column::class);
                if (null !== $columnAnnotation) {
                    $val = $context->getNavigator()->accept($data[$property->getName()], [
                        'name' => $columnAnnotation->type == 'date' ? 'DateTime' : $columnAnnotation->type,
                        'params' => []
                    ]);

                    $property->setAccessible(true);
                    $property->setValue($entity, $val);
                    continue;
                }

                // solo quedan las entidades

                /** @var DeserializeEntity $deserializeEntityAnnotation */
                $deserializeEntityAnnotation = $this->reader->getPropertyAnnotation($property, DeserializeEntity::class);
                if (null === $deserializeEntityAnnotation) {
                    continue;
                }

                // TODO armo el procedimiento para las collecciones, falta procedimiento para solo entidades
                $manyToManyAnnotation = $this->reader->getPropertyAnnotation($property, ManyToMany::class);
                if (null !== $manyToManyAnnotation) {
//                    $subResources = new ArrayCollection();
                    if (count($data[$property->getName()]) > 0) {
                        foreach ($data[$property->getName()] as $subResource) {
                            // agrego cada subrecurso a la entidad con el setter de las notas
                            $entity->{$deserializeEntityAnnotation->setter}($context->getNavigator()->accept($subResource, [
                                'name' => $manyToManyAnnotation->targetEntity,
                                'params' => []
                            ]));
                        }
                    }
//                    $property->setAccessible(true);
//                    $property->setValue($entity, $subResources);
                    continue;
                }

                throw new LogicException("Can't handle property '{$property->getName()}' of {$reflection->getShortName()} entity");
            }

            return $entity;
        }
    }
}