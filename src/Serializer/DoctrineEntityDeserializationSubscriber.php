<?php


namespace App\Serializer;


use App\Annotation\DeserializeEntity;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ManyToMany;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DoctrineEntityDeserializationSubscriber implements EventSubscriberInterface
{
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var RegistryInterface
     */
    private $doctrineRepository;

    public function __construct(Reader $reader, RegistryInterface $doctrineRepository)
    {
        $this->reader = $reader;
        $this->doctrineRepository = $doctrineRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.pre_deserialize',
                'method' => 'onPreDeserialize',
                'format' => 'json'
            ],
            [
                'event' => 'serializer.post_deserialize',
                'method' => 'onPostDeserialize',
                'format' => 'json'
            ]
        ];
    }

    /**
     * @param PreDeserializeEvent $event
     * @throws ReflectionException
     */
    public function onPreDeserialize(PreDeserializeEvent $event)
    {
        $type = $event->getType()['name'];

        if (!class_exists($type)) {
            return;
        }

        $data = $event->getData();
        $class = new ReflectionClass($type);

        foreach ($class->getProperties() as $property) {
            // no se envio la propiedad
            if (!isset($data[$property->name])) {
                continue;
            }

            /** @var DeserializeEntity $annotation */
            $annotation = $this->reader->getPropertyAnnotation($property, DeserializeEntity::class);

            if ($annotation === null || class_exists($annotation->type)){
                continue;
            }

            $data[$property->name] = [$annotation->idField => $data[$property->name]];
        }

        $event->setData($data);
    }

    public function onPostDeserialize(ObjectEvent $event)
    {
        $type = $event->getType()['name'];

        if (!class_exists($type)) {
            return;
        }

        $object = $event->getObject();

        $reflection = new ReflectionObject($object);

        // por cada propiedad del objeto
        foreach ($reflection->getProperties() as $property) {
            /** @var DeserializeEntity $annotation */
            $annotation = $this->reader->getPropertyAnnotation($property, DeserializeEntity::class);

            // si no tiene la anotacion o no es una Entidad
            if ($annotation === null || !class_exists($annotation->type)){
                continue;
            }

            // Si el Recurso NO tiene el metodo para setear el Subrecurso
            if (!$reflection->hasMethod($annotation->setter)) {
                throw new LogicException("Object {$reflection->getName()} does not have the {$annotation->setter} method");
            }

            $property->setAccessible(true);

            $repository = $this->doctrineRepository->getRepository($annotation->type);

            // relación ManyToOne
            if (!$this->reader->getPropertyAnnotation($property, ManyToMany::class)) {
                $entity = $property->getValue($object);

                if ($entity === null) {
                    return;
                }

                // TODO copy from many to OR refactor (dry)
                $entityId = $entity->{$annotation->idGetter}();

                $entity = $repository->find($entityId);

                if ($entity === null) {
                    throw new NotFoundHttpException("Resource {$reflection->getShortName()}/$entityId");
                }

                $object->{$annotation->setter}{$entity};
            // relación ManyToMany
            } else {
                // colección de subrecursos
                $entityCollection = $property->getValue($object);

                if ($entityCollection === null || !count($entityCollection) > 0) {
                    continue;
                }

                // por cada subrecurso
                foreach ($entityCollection as $subresourceDeserialized) {
                    $entityId = $subresourceDeserialized->{$annotation->idGetter}();

                    $entity = $repository->find($entityId);

                    if ($entity === null) {
                        // TODO new subresource (revisar si corresponde crear un subrecurso que no existe)
                        throw new NotFoundHttpException("Resource {$reflection->getShortName()}/$entityId");
                    }

                    $entityReflection = new ReflectionObject($entity);

                    foreach ($entityReflection->getProperties() as $subresourceProperty) {
                        $subresourceProperty->setAccessible(true);
                        $deserializedValue = $subresourceProperty->getValue($subresourceDeserialized);
                        if ($deserializedValue === null) {
                            continue;
                        }

                        if ($subresourceProperty->getValue($entity) != $deserializedValue) {
                            if ($this->reader->getPropertyAnnotation($subresourceProperty, ManyToMany::class)) {
                                continue;
                            }
                            throw new BadRequestHttpException("Cant modify a subresource, {$entityReflection->getShortName()}/$entityId");
                        }
                    }

                    // reemplazo el recurso deserializado por la entidad
                    $object->{$annotation->unsetter}($subresourceDeserialized);
                    $object->{$annotation->setter}($entity);
                }
            }
        }
    }
}