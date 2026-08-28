<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTime $birthdate = null;

    #[ORM\Column]
    private ?\DateTime $deathdate = null;

    #[ORM\ManyToOne(inversedBy: 'people')]
    private ?Gender $gender = null;

    #[ORM\ManyToOne(inversedBy: 'people')]
    private ?Family $family = null;

    /**
     * @var Collection<int, Union>
     */
    #[ORM\ManyToMany(targetEntity: Union::class, mappedBy: 'person1')]
    private Collection $unions;

    #[ORM\ManyToOne(inversedBy: 'children')]
    private ?Union $childishUnion = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class)]
    private Collection $parent;

    /**
     * @var Collection<int, Events>
     */
    #[ORM\ManyToMany(targetEntity: Events::class, mappedBy: 'persons')]
    private Collection $events;

    public function __construct()
    {
        $this->unions = new ArrayCollection();
        $this->parent = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTime $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getDeathdate(): ?\DateTime
    {
        return $this->deathdate;
    }

    public function setDeathdate(\DateTime $deathdate): static
    {
        $this->deathdate = $deathdate;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getFamily(): ?Family
    {
        return $this->family;
    }

    public function setFamily(?Family $family): static
    {
        $this->family = $family;

        return $this;
    }

    /**
     * @return Collection<int, Union>
     */
    public function getUnions(): Collection
    {
        return $this->unions;
    }

    public function addUnion(Union $union): static
    {
        if (!$this->unions->contains($union)) {
            $this->unions->add($union);
            $union->addPerson1($this);
        }

        return $this;
    }

    public function removeUnion(Union $union): static
    {
        if ($this->unions->removeElement($union)) {
            $union->removePerson1($this);
        }

        return $this;
    }

    public function getChildishUnion(): ?Union
    {
        return $this->childishUnion;
    }

    public function setChildishUnion(?Union $childishUnion): static
    {
        $this->childishUnion = $childishUnion;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getParent(): Collection
    {
        return $this->parent;
    }

    public function addParent(self $parent): static
    {
        if (!$this->parent->contains($parent)) {
            $this->parent->add($parent);
        }

        return $this;
    }

    public function removeParent(self $parent): static
    {
        $this->parent->removeElement($parent);

        return $this;
    }

    /**
     * @return Collection<int, Events>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Events $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addPerson($this);
        }

        return $this;
    }

    public function removeEvent(Events $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removePerson($this);
        }

        return $this;
    }
}
