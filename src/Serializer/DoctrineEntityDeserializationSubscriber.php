<?php


namespace App\Serializer;


use App\Annotation\DeserializeEntity;
use App\Service\EntityMerger;
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
    /**
     * @var EntityMerger
     */
    private $entityMerger;

    /**
     * DoctrineEntityDeserializationSubscriber constructor.
     * @param Reader $reader
     * @param RegistryInterface $doctrineRepository
     * @param EntityMerger $entityMerger
     */
    public function __construct(Reader $reader, RegistryInterface $doctrineRepository, EntityMerger $entityMerger)
    {
        $this->reader = $reader;
        $this->doctrineRepository = $doctrineRepository;
        $this->entityMerger = $entityMerger;
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
//            [
//                'event' => 'serializer.post_deserialize',
//                'method' => 'onPostDeserialize',
//                'format' => 'json'
//            ]
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

            // TODO revisar cuando no es un array de entidades
            $data[$property->name] = [$annotation->idField => $data[$property->name]];
        }
        $event->setData($data);
    }

//    public function onPostDeserialize(ObjectEvent $event)
//    {
//        $type = $event->getType()['name'];
//
//        if (!class_exists($type)) {
//            return;
//        }
//
//        $object = $event->getObject();
//
//        dump($object);
//
//        $reflection = new ReflectionObject($object);
//
//        // por cada propiedad del objeto (busco las propidades que son Relaciones o Entidades)
//        foreach ($reflection->getProperties() as $property) {
//            /** @var DeserializeEntity $annotation */
//            $annotation = $this->reader->getPropertyAnnotation($property, DeserializeEntity::class);
//
//            // si no tiene la anotacion o no es una Entidad
//            if ($annotation === null || !class_exists($annotation->type)){
//                continue;
//            }
//
//            // Si el Recurso NO tiene el metodo para setear el Subrecurso
//            if (!$reflection->hasMethod($annotation->setter)) {
//                throw new LogicException("Object {$reflection->getName()} does not have the {$annotation->setter} method");
//            }
//
//            // Si el Recurso NO tiene el metodo para remover el Subrecurso
//            if (!$reflection->hasMethod($annotation->unsetter)) {
//                throw new LogicException("Object {$reflection->getName()} does not have the {$annotation->unsetter} method");
//            }
//
//            $property->setAccessible(true);
//
//            $repository = $this->doctrineRepository->getRepository($annotation->type);
//
//            $deserializedSubResources = $property->getValue($object);
//
//            if ($deserializedSubResources === null || !count($deserializedSubResources) > 0) {
//                continue;
//            }
//
//            // por cada subrecurso
//            foreach ($deserializedSubResources as $deserializedSubResource) {
//                $entityId = $deserializedSubResource->{$annotation->idGetter}();
//                $entity = $repository->find($entityId);
//
//                if (null === $entity) {
//                    // TODO new subresource (revisar si corresponde crear un subrecurso que no existe)
//                    throw new NotFoundHttpException("Resource {$property->getName()}/$entityId");
//                }
//
//                $entityReflection = new ReflectionObject($entity);
//
//                foreach ($entityReflection->getProperties() as $subresourceProperty) {
//                    $subresourceProperty->setAccessible(true);
//                    $deserializedValue = $subresourceProperty->getValue($deserializedSubResource);
//                    if ($deserializedValue === null) {
//                        continue;
//                    }
//
//                    if ($subresourceProperty->getValue($entity) != $deserializedValue) {
//                        if ($this->reader->getPropertyAnnotation($subresourceProperty, ManyToMany::class)) {
//                            continue;
//                        }
//                        throw new BadRequestHttpException("Cant modify subresource {$entityReflection->getShortName()}/$entityId");
//                    }
//                }
//
//                // reemplazo el recurso deserializado por la entidad
//                $object->{$annotation->unsetter}($deserializedSubResource);
//                $object->{$annotation->setter}($entity);
//            }
//        }
//    }
}