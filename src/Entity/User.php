<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 64)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 64)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $lastName = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?bool $isAdmin = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    /**
     * @var Collection<int, Auth>
     */
    #[ORM\OneToMany(targetEntity: Auth::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $auths;

    public function __construct()
    {
        $this->auths = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;

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

    /**
     * @return Collection<int, Auth>
     */
    public function getAuths(): Collection
    {
        return $this->auths;
    }

    public function addAuth(Auth $auth): static
    {
        if (!$this->auths->contains($auth)) {
            $this->auths->add($auth);
            $auth->setUser($this);
        }

        return $this;
    }

    public function removeAuth(Auth $auth): static
    {
        if ($this->auths->removeElement($auth)) {
            // set the owning side to null (unless already changed)
            if ($auth->getUser() === $this) {
                $auth->setUser(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = ['User'];
        if ($this->isAdmin) {
            $roles[] = 'Admin';
        }
        return $roles;
    }

    public function eraseCredentials(): void{}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
