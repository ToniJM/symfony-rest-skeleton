<?php


namespace App\Serializer\Handler;

use App\Annotation\DeserializeEntity;
use App\Entity\Date;
use App\Entity\Note;
use App\Entity\Persona;
use App\Exception\ValidationException;
use DateTime;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use LogicException;
use ReflectionObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DatesHandler implements SubscribingHandlerInterface
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
        $array = [];

        $array[] = [
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'format' => 'json',
            'type' => 'date',
            'method' => 'serializeDate'
        ];

        return $array;
    }

    public function serializeDate(JsonSerializationVisitor $visitor, DateTime $date)
    {
        return $date->format('Y-m-d');
    }
}