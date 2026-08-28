<?php

namespace App\Entity;

use App\Repository\PeriodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodRepository::class)]
class Period
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    private ?string $color = null;

    /** @var Collection<int, Events> */
    #[ORM\OneToMany(targetEntity: Events::class, mappedBy: 'period')]
    private Collection $events;

    public function __construct() { $this->events = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getColor(): ?string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }

    /** @return Collection<int, Events> */
    public function getEvents(): Collection { return $this->events; }

    public function addEvent(Events $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setPeriod($this);
        }
        return $this;
    }

    public function removeEvent(Events $event): static
    {
        if ($this->events->removeElement($event) && $event->getPeriod() === $this) {
            $event->setPeriod(null);
        }
        return $this;
    }
}
