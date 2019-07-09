<?php


namespace App\Service;


use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Id;
use InvalidArgumentException;
use ReflectionException;
use ReflectionObject;

class EntityMerger
{
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param $entity
     * @param $patch
     * @throws ReflectionException
     */
    public function merge($entity, $patch)
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
            throw new InvalidArgumentException("Cannot merge object of class $patchClassName witht object of class $entityClassName");
        }

        $entityReflection = new ReflectionObject($entity);
        $patchReflection = new ReflectionObject($patch);

        foreach ($patchReflection->getProperties() as $patchProperty) {
            $patchProperty->setAccessible(true);
            $patchPropertyValue = $patchProperty->getValue($patch);

            // tiene un valor
            if ($patchPropertyValue === null) {
                continue;
            }

            // la entidad tiene esa propiedad
            if (!$entityReflection->hasProperty($patchProperty->getName())) {
                continue;
            }

            $entityProperty = $entityReflection->getProperty($patchProperty->getName());
            $annotation = $this->reader->getPropertyAnnotation($entityProperty, Id::class);

            // no modificamos el ID
            if (!null === $annotation) {
                continue;
            }

            $entityProperty->setAccessible(true);
            $entityProperty->setValue($entity, $patchPropertyValue);
        }
    }
}