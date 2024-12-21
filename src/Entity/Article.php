<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    private ?int $qteStock = null;

    #[ORM\Column(nullable: true)]
    private ?int $qteRestante = null;

    #[ORM\Column(nullable: true)]
    private ?float $prix = null;


      /**
     * @ORM\Column(type="integer")
     */
    private $quantite;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Approvisionnement::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $approvisionnements;

    public function __construct()
    {
        $this->approvisionnements = new ArrayCollection();
    }
    

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getQteStock(): ?int
    {
        return $this->qteStock;
    }

    public function setQteStock(?int $qteStock): self
    {
        $this->qteStock = $qteStock;
        return $this;
    }

    public function getQteRestante(): ?int
    {
        return $this->qteRestante;
    }

    public function setQteRestante(?int $qteRestante): self
    {
        $this->qteRestante = $qteRestante;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    /**
     * @return Collection<int, Approvisionnement>
     */
    public function getApprovisionnements(): Collection
    {
        return $this->approvisionnements;
    }

    public function addApprovisionnement(Approvisionnement $approvisionnement): self
    {
        if (!$this->approvisionnements->contains($approvisionnement)) {
            $this->approvisionnements[] = $approvisionnement;
            $approvisionnement->setArticle($this);
        }
        return $this;
    }

    public function removeApprovisionnement(Approvisionnement $approvisionnement): self
    {
        if ($this->approvisionnements->removeElement($approvisionnement)) {
            if ($approvisionnement->getArticle() === $this) {
                $approvisionnement->setArticle(null);
            }
        }
        return $this;
    }
}
