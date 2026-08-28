<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'town_event_media')]
class TownEventMedia
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'media'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TownEvent $event = null;
    #[ORM\Column(length: 20)]
    private ?string $type = null;
    #[ORM\Column(length: 2048)]
    private ?string $url = null;
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;
    #[ORM\Column(length: 120)]
    private ?string $mimeType = null;
    public function getId(): ?int { return $this->id; }
    public function getEvent(): ?TownEvent { return $this->event; }
    public function setEvent(TownEvent $event): static { $this->event = $event; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(string $url): static { $this->url = $url; return $this; }
    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $originalName): static { $this->originalName = $originalName; return $this; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(string $mimeType): static { $this->mimeType = $mimeType; return $this; }
}
