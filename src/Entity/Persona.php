<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\Annotation as App;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PersonaRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Serializer\ExclusionPolicy("ALL")
 * @Hateoas\Relation("self", href=@Hateoas\Route("get_persona", parameters={"persona"="expr(object.getId())"}))
 * @Hateoas\Relation(
 *     "dates",
 *     href=@Hateoas\Route("get_personas_dates", parameters={"persona"="expr(object.getId())"})
 * )
 */
class Persona
{
    use Timestamps;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank()
     * @Assert\Length(min=3, max=50)
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $last_name;

    /**
     * @ORM\Column(type="string", length=30)
     * @Assert\Length(max=30)
     * @Serializer\Groups({"Default", "Deserialize"})
     * @Serializer\Expose()
     */
    private $alias;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Date", mappedBy="personas")
     * @App\DeserializeEntity(type="App\Entity\Date", idField="id", idGetter="getId", setter="addDate", unsetter="removeDate")
     */
    private $dates;

    public function __construct()
    {
        $this->dates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;
        $this->alias = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getAlias(): ?string
    {
        if (!$this->alias && $this->first_name) {
            $this->alias = $this->first_name;
        }
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): self
    {
        if (!$this->getAlias() AND $this->getFirstName()) {
            $this->setAlias($this->getFirstName());
        }
        return $this;
    }

    /**
     * @return Collection|Date[]
     */
    public function getDates(): Collection
    {
        return $this->dates;
    }

    public function addDate(Date $date): self
    {
        if (!$this->dates->contains($date)) {
            $this->dates[] = $date;
            $date->addPersona($this);
        }

        return $this;
    }

    public function removeDate(Date $date): self
    {
        if ($this->dates->contains($date)) {
            $this->dates->removeElement($date);
            $date->removePersona($this);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getAlias();
    }
}
