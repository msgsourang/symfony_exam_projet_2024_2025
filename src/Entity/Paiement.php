<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Dette;

#[ORM\Entity]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $montant;

    #[ORM\ManyToOne(targetEntity: Dette::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false)]
    private Dette $dette;

    public function __construct(float $montant, Dette $dette)
    {
        $this->date = new \DateTimeImmutable();
        $this->montant = $montant;
        $this->dette = $dette;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDette(): Dette
    {
        return $this->dette;
    }

    public function setDette(Dette $dette): self
    {
        $this->dette = $dette;
        return $this;
    }
}
