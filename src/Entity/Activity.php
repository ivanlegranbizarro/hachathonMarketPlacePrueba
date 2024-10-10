<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'This activity already exists')]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Activity name cannot be empty')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Activity name must be at least 3 characters long', maxMessage: 'Activity name cannot be longer than 255 characters')]
    #[Groups(['read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Activity description cannot be empty')]
    #[Assert\Length(min: 10, max: 500, minMessage: 'Activity description must be at least 10 characters long', maxMessage: 'Activity description cannot be longer than 255 characters')]
    #[Groups(['read'])]
    private ?string $description = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'activities')]
    private Collection $participants; // Sin grupo para ocultarlo en serialización

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Activity duration cannot be empty')]
    #[Assert\Range(
        min: 30,
        max: 120,
        notInRangeMessage: 'Activity duration must be between {{ min }} and {{ max }} minutes'
    )]
    #[Groups(['read'])]
    private ?int $duration = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(User $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }
}
