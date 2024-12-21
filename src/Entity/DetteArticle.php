<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DetteArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dette::class, inversedBy: 'detteArticles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dette $dette = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\Column(type: 'integer')]
    private int $quantite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDette(): ?Dette
    {
        return $this->dette;
    }

    public function setDette(Dette $dette): self
    {
        $this->dette = $dette;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }
}
