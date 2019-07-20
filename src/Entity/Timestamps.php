<?php


namespace App\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait Timestamps
{
    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"Default"})
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"Default"})
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $updatedAt;

    /**
     * @ORM\PrePersist()
     */
    public function createdAt()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function updatedAt()
    {
        $this->updatedAt = new DateTime();
    }
}