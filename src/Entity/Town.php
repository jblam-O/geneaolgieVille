<?php

namespace App\Entity;

use App\Repository\TownRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TownRepository::class)]
#[ORM\Table(name: 'town')]
class Town
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 120, unique: true)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

    /** @var Collection<int, TownEvent> */
    #[ORM\OneToMany(targetEntity: TownEvent::class, mappedBy: 'town', orphanRemoval: true)]
    #[ORM\OrderBy(['year' => 'ASC'])]
    private Collection $events;

    public function __construct() { $this->events = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(float $latitude): static { $this->latitude = $latitude; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(float $longitude): static { $this->longitude = $longitude; return $this; }
    /** @return Collection<int, TownEvent> */
    public function getEvents(): Collection { return $this->events; }
}
