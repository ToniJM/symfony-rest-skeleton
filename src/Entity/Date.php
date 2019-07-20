<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Annotation as App;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DateRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Serializer\ExclusionPolicy("ALL")
 * @Hateoas\Relation("self", href=@Hateoas\Route("get_date", parameters={"date"="expr(object.getId())"}))
 */
class Date
{
    use Timestamps;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     * @Serializer\Type("DateTime<'Y-m-d'>")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     * @Assert\Date()
     * @Assert\NotBlank(groups={"Default"})
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=30)
     * @Assert\NotBlank(groups={"Default"})
     * @Assert\Length(max=30, min=4, groups={"Default", "Patch"})
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Persona", inversedBy="dates")
     * @App\DeserializeEntity(type="App\Entity\Persona", idField="id", idGetter="getId", setter="addPersona", unsetter="removePersona")
     * @Serializer\Groups({"Deserialize"})
     * @Serializer\Expose()
     * @Serializer\MaxDepth(1)
     */
    private $personas;

    public function __construct()
    {
        $this->personas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|Persona[]
     */
    public function getPersonas(): Collection
    {
        return $this->personas;
    }

    public function addPersona(Persona $persona): self
    {
        if (null !== $this->personas && !$this->personas->contains($persona)) {
            $this->personas[] = $persona;
            $persona->addDate($this);
        }

        return $this;
    }

    public function removePersona(Persona $persona): self
    {
        if (null !== $this->personas && $this->personas->contains($persona)) {
            $this->personas->removeElement($persona);
        }

        return $this;
    }

    function getDay(): int
    {
        if ($this->getDate()) {
            return $this->getDate()->format('d');
        }
        return null;
    }

    function getMonth(): int
    {
        if ($this->getDate()) {
            return $this->getDate()->format('m');
        }
        return null;
    }

    public function __toString()
    {
        return $this->getName() ? $this->getName() : "nieva";
    }
}
