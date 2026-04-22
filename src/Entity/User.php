<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: 'users')]
class User
{
    #[Id]
    #[Column(type: Types::STRING, length: 36)]
    private string $id;

    #[Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[Column(type: Types::STRING)]
    private string $password;

    #[Column(type: Types::STRING, length: 255)]
    private string $name;

    #[Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $updatedAt;

    #[OneToMany(targetEntity: Conversation::class, mappedBy: 'user')]
    private Collection $conversations;

    public function __construct(string $email, string $password, string $name)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->conversations = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTime();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTime();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTime();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
        $this->updatedAt = new DateTime();
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): void
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setUser($this);
        }
    }

    public function removeConversation(Conversation $conversation): void
    {
        $this->conversations->removeElement($conversation);
    }
}
