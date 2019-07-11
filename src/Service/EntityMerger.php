<?php


namespace App\Service;


use App\Annotation\DeserializeEntity;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use InvalidArgumentException;
use LogicException;
use ReflectionException;
use ReflectionObject;

class EntityMerger
{
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(Reader $reader, EntityManagerInterface $manager)
    {
        $this->reader = $reader;
        $this->manager = $manager;
    }

    /**
     * @param $entity
     * @param $patch
     * @param array $map Si esta seteado se ignoran las propiedades que no estan en el array
     * @param bool|null $overrideCollections Sobreescribe collecciones de entidades
     * @throws ReflectionException
     */
    public function merge($entity, $patch, array $map = null, bool $overrideCollections = null)
    {
        $entityClassName = get_class($entity);
        if ($entityClassName === false) {
            throw new InvalidArgumentException('$entity is not an Entity object');
        }

        $patchClassName = get_class($patch);
        if ($patchClassName === false) {
            throw new InvalidArgumentException('$patch is not an Entity object');
        }

        if (!is_a($patch, $entityClassName)) {
            throw new InvalidArgumentException("Cannot merge object of class $patchClassName with object of class $entityClassName");
        }

        $entityReflection = new ReflectionObject($entity);
        $patchReflection = new ReflectionObject($patch);

        foreach ($patchReflection->getProperties() as $patchProperty) {
            $patchProperty->setAccessible(true);
            $patchPropertyValue = $patchProperty->getValue($patch);

            // no esta seteado el array map
            if (null === $map) {
                // No existe esa propiedad en la entidad
                if (!$entityReflection->hasProperty($patchProperty->getName())) {
                    continue;
                }
            }
            else {
                // no esta seteada la propiedad en map y
                // no es null la propiedad
                if (!array_key_exists($patchProperty->getName(), $map)) {
                    continue;
                }
            }

//            dump($patchProperty->getName());

            $entityProperty = $entityReflection->getProperty($patchProperty->getName());
            $idAnnotation = $this->reader->getPropertyAnnotation($entityProperty, Id::class);

            // No modificamos el ID
            if (null !== $idAnnotation) {
                continue;
            }

            $annotation = $this->reader->getPropertyAnnotation($patchProperty, DeserializeEntity::class);

            // No tiene la anotación deserialize
            if (null === $annotation) {
                // cambio el valor en la entidad
                if ($entityProperty->getName() === 'createdAt') {
                    continue;
                }
                if ($entityProperty->getName() === 'updatedAt') {
                    $entity->{$entityProperty->getName()}();
                    continue;
                }
                $entityProperty->setAccessible(true);
                $entityProperty->setValue($entity, $patchPropertyValue);
            }
            else {
                // Si el Recurso NO tiene el metodo para setear el Subrecurso
                if (!$entityReflection->hasMethod($annotation->setter)) {
                    throw new LogicException("Object {$entityReflection->getName()} does not have the {$annotation->setter} method");
                }

//                $subresourceRepository = $this->manager->getRepository($annotation->type);

                // no es un array de entidades (no es relación ManyToOne)
                if (!$this->reader->getPropertyAnnotation($entityProperty, ManyToMany::class)) {
                    // TODO revisar que funcione cuando no es un array
                    $entity->{$annotation->setter}{$patchPropertyValue};
                }
                // relación ManyToMany (array)
                else {
                    // colección de subrecursos
                    // TODO revisar si el array viene null o vacio cuando no se seteo en la request
//                    if (count($patchPropertyValue) <= 0) {
//                        // TODO decidir que hacer cuando viene vacio (eliminar todos o no hacer nada)
//                        // ahora no hace nada (salta a la sig propiedad)
//                        continue;
//                    }

                    if ($overrideCollections) {
                        $entityProperty->setAccessible(true);
                        $entityPropertyValue = $entityProperty->getValue($entity);
                        foreach ($entityPropertyValue as $oldSubresource) {
                            $entity->{$annotation->unsetter}($oldSubresource);
                        }
                    }

                    foreach ($patchPropertyValue as $subresource) {
                        $patch->{$annotation->unsetter}($subresource);
                        $entity->{$annotation->unsetter}($subresource);
                        $entity->{$annotation->setter}($subresource);
                    }
                }
            }
        }
    }
}