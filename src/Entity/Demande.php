<?php

namespace App\Entity;

use App\Repository\DemandeRepository;
use App\Enum\StatutEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
class Demande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?int $tel = null;

    #[ORM\Column(length: 255)]
    private ?string $nomComplet = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column(type: 'string', enumType: StatutEnum::class)]
    private StatutEnum $statut;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\OneToMany(targetEntity: DemandeArticle::class, mappedBy: 'demande', cascade: ['persist', 'remove'])]
    private Collection $demandeArticles;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $etat = null;

    /**
     * @ORM\Column(type="text")
     */
    private $description;


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function __construct()
    {
        $this->demandeArticles = new ArrayCollection();
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getTel(): ?int
    {
        return $this->tel;
    }

    public function setTel(int $tel): static
    {
        $this->tel = $tel;
        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): static
    {
        $this->nomComplet = $nomComplet;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getStatut(): StatutEnum
    {
        return $this->statut;
    }

    public function setStatut(StatutEnum $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getStatutLabel(): string
    {
        return $this->statut->value; 
    }

    public function getDemandeArticles(): Collection
    {
        return $this->demandeArticles;
    }

    public function addDemandeArticle(DemandeArticle $demandeArticle): self
    {
        if (!$this->demandeArticles->contains($demandeArticle)) {
            $this->demandeArticles->add($demandeArticle);
            $demandeArticle->setDemande($this);
        }

        return $this;
    }

    public function removeDemandeArticle(DemandeArticle $demandeArticle): self
    {
        if ($this->demandeArticles->removeElement($demandeArticle)) {
            if ($demandeArticle->getDemande() === $this) {
                $demandeArticle->setDemande(null);
            }
        }

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }
}
