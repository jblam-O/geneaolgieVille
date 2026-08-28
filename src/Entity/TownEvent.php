<?php

namespace App\Entity;

use App\Repository\TownEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TownEventRepository::class)]
#[ORM\Table(name: 'town_event')]
class TownEvent
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'events'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Town $town = null;
    #[ORM\Column]
    private ?int $year = null;
    #[ORM\Column(length: 80)]
    private ?string $dateLabel = null;
    #[ORM\Column(length: 180)]
    private ?string $title = null;
    #[ORM\Column(type: 'text')]
    private ?string $summary = null;
    #[ORM\Column(type: 'text')]
    private ?string $detail = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;
    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;
    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;
    /** @var Collection<int, TownEventMedia> */
    #[ORM\OneToMany(targetEntity: TownEventMedia::class, mappedBy: 'event', cascade: ['persist'], orphanRemoval: true)]
    private Collection $media;

    public function __construct() { $this->media = new ArrayCollection(); $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getTown(): ?Town { return $this->town; }
    public function setTown(Town $town): static { $this->town = $town; return $this; }
    public function getYear(): ?int { return $this->year; }
    public function setYear(int $year): static { $this->year = $year; return $this; }
    public function getDateLabel(): ?string { return $this->dateLabel; }
    public function setDateLabel(string $dateLabel): static { $this->dateLabel = $dateLabel; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getSummary(): ?string { return $this->summary; }
    public function setSummary(string $summary): static { $this->summary = $summary; return $this; }
    public function getDetail(): ?string { return $this->detail; }
    public function setDetail(string $detail): static { $this->detail = $detail; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): static { $this->latitude = $latitude; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): static { $this->longitude = $longitude; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    /** @return Collection<int, TownEventMedia> */
    public function getMedia(): Collection { return $this->media; }
    public function addMedia(TownEventMedia $media): static { if (!$this->media->contains($media)) { $this->media->add($media); $media->setEvent($this); } return $this; }
}
