<?php

namespace App\Entity;

use App\Repository\UnionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnionRepository::class)]
#[ORM\Table(name: '`union`')]
class Union
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: 'unions')]
    private Collection $person1;

    #[ORM\Column]
    private ?\DateTime $startdate = null;

    #[ORM\Column]
    private ?\DateTime $enddate = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\OneToMany(targetEntity: Person::class, mappedBy: 'childishUnion')]
    private Collection $children;

    public function __construct()
    {
        $this->person1 = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Person>
     */
    public function getPerson1(): Collection
    {
        return $this->person1;
    }

    public function addPerson1(Person $person1): static
    {
        if (!$this->person1->contains($person1)) {
            $this->person1->add($person1);
        }

        return $this;
    }

    public function removePerson1(Person $person1): static
    {
        $this->person1->removeElement($person1);

        return $this;
    }

    public function getStartdate(): ?\DateTime
    {
        return $this->startdate;
    }

    public function setStartdate(\DateTime $startdate): static
    {
        $this->startdate = $startdate;

        return $this;
    }

    public function getEnddate(): ?\DateTime
    {
        return $this->enddate;
    }

    public function setEnddate(\DateTime $enddate): static
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * @return Collection<int, Person>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Person $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setChildishUnion($this);
        }

        return $this;
    }

    public function removeChild(Person $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getChildishUnion() === $this) {
                $child->setChildishUnion(null);
            }
        }

        return $this;
    }
}
