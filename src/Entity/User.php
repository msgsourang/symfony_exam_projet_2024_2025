<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\OneToOne(targetEntity: Client::class, inversedBy: 'userAccount')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Client $client = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];
    private const ROLES_ALLOWED = ['ROLE_USER', 'ROLE_BOUTIQUIER', 'ROLE_VENDEUR',  'ROLE_CLIENT', 'ROLE_ADMIN'];

    #[ORM\Column(type: 'boolean')]
    private bool $statut = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        if ($client === null && $this->client !== null) {
            $this->client->setUserAccount(null);
        }

        if ($client !== null && $client->getUserAccount() !== $this) {
            $client->setUserAccount($this);
        }

        $this->client = $client;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    public function getRoles(): array
    {
        return $this->roles ?: ['ROLE_CLIENT'];
    }
    public function setRoles(array $roles): self
    {
        foreach ($roles as $role) {
            if (!in_array($role, self::ROLES_ALLOWED, true)) {
                throw new \InvalidArgumentException(sprintf('Le rôle "%s" n\'est pas autorisé.', $role));
            }
        }

        $this->roles = $roles;

        return $this;
    }


    public function getSalt(): ?string
    {
        return null; 
    }

    public function eraseCredentials(): void
    {
        
    }
    public function getStatut(): bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): static
    {
        $this->statut = $statut;
        return $this;
    }
 

}

