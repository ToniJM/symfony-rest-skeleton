<?php


namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class DeserializeEntity
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class DeserializeEntity
{
    /**
     * @var string
     * @Required()
     */
    public $type;

    /**
     * @var string
     * @Required()
     */
    public $idField;

    /**
     * @var string
     * @Required()
     */
    public $setter;

    /**
     * @var string
     * @Required()
     */
    public $unsetter;

    /**
     * @var string
     * @Required()
     */
    public $idGetter;
}