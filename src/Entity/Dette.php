<?php

namespace App\Entity;

use App\Repository\DetteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetteRepository::class)]
class Dette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantVerser = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'non_solde'])]
    private string $statut = 'non_solde';

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'dettes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\OneToOne(targetEntity: Demande::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Demande $demande = null;

    #[ORM\OneToMany(mappedBy: 'dette', targetEntity: Paiement::class, cascade: ['persist', 'remove'])]
    private Collection $paiements;

    #[ORM\OneToMany(mappedBy: 'dette', targetEntity: DetteArticle::class, cascade: ['persist', 'remove'])]
    private Collection $detteArticles;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $archived = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="dettes")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user; 



    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->detteArticles = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): self
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getMontantVerser(): ?float
    {
        return $this->montantVerser;
    }

    public function setMontantVerser(float $montantVerser): self
    {
        $this->montantVerser = $montantVerser;

        $this->statut = $this->getMontantRestant() <= 0 ? 'solder' : 'non_solde';

        return $this;
    }

    public function getMontantRestant(): float
    {
        return max(0, $this->montant - ($this->montantVerser ?? 0));
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDemande(): ?Demande
    {
        return $this->demande;
    }

    public function setDemande(?Demande $demande): self
    {
        $this->demande = $demande;
        return $this;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): self
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements[] = $paiement;
            $paiement->setDette($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): self
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getDette() === $this) {
                $paiement->setDette(null);
            }
        }

        return $this;
    }
    public function getDetteArticles(): Collection
{
    return $this->detteArticles;
}

public function addDetteArticle(DetteArticle $detteArticle): self
{
    if (!$this->detteArticles->contains($detteArticle)) {
        $this->detteArticles->add($detteArticle);
        $detteArticle->setDette($this);
    }

    return $this;
}

public function removeDetteArticle(DetteArticle $detteArticle): self
{
    if ($this->detteArticles->removeElement($detteArticle)) {
        if ($detteArticle->getDette() === $this) {
            $detteArticle->setDette(null);
        }
    }

    return $this;
}
public function getArchived(): ?bool
{
    return $this->archived;
}

public function setArchived(bool $archived): self
{
    $this->archived = $archived;
    return $this;
}
public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
