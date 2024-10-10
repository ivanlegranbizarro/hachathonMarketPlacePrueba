<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read', 'show'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email(
        message: 'Please enter a valid email',
        groups: ['create', 'edit']
    )]
    #[Assert\NotBlank(
        message: 'Please enter an email',
        groups: ['create']
    )]
    #[Groups(['read', 'write', 'show'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['show'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(
        message: 'Please enter a password',
        groups: ['create']
    )]
    #[Assert\Length(
        min: 6,
        minMessage: 'Password must be at least 6 characters long',
        groups: ['create']
    )]
    #[Groups(['write'])]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Please enter your name',
        groups: ['create']
    )]
    #[Assert\Length(
        min: 2,
        minMessage: 'Name must be at least 2 characters long',
        groups: ['create', 'edit']
    )]
    #[Groups(['read', 'write', 'show'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Please enter your last name',
        groups: ['create']
    )]
    #[Assert\Length(
        min: 3,
        minMessage: 'Last name must be at least 3 characters long',
        groups: ['create', 'edit']
    )]
    #[Groups(['read', 'write', 'show'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Please enter your username',
        groups: ['create']
    )]
    #[Assert\Length(
        min: 5,
        minMessage: 'Username must be at least 5 characters long',
        groups: ['create', 'edit']
    )]
    #[Groups(['read', 'write', 'show'])]
    private ?string $username = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank(
        message: 'Please enter your birthday',
        groups: ['create']
    )]
    #[Groups(['read', 'write', 'show'])]
    private ?\DateTimeImmutable $birthday = null;

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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
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

    public function eraseCredentials(): void {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday->format('Y-m-d');
    }

    public function setBirthday(\DateTimeImmutable $birthday): static
    {
        $this->birthday = $birthday;
        return $this;
    }

    #[Groups(['show'])]
    public function getAge(): int
    {
        return $this->birthday->diff(new \DateTimeImmutable())->y;
    }
}
