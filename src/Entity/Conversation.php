<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: 'conversations')]
class Conversation
{
    #[Id]
    #[Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'conversations')]
    private User $user;

    #[Column(type: Types::STRING, length: 255)]
    private string $title;

    #[Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Column(type: Types::STRING, length: 100, options: ['default' => 'gpt-4'])]
    private string $aiModel = 'gpt-4';

    #[Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $updatedAt;

    #[OneToMany(targetEntity: Message::class, mappedBy: 'conversation', cascade: ['persist', 'remove'])]
    private Collection $messages;

    public function __construct(User $user, string $title, string $aiModel = 'gpt-4')
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->title = $title;
        $this->aiModel = $aiModel;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->messages = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->updatedAt = new DateTime();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new DateTime();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new DateTime();
    }

    public function getAiModel(): string
    {
        return $this->aiModel;
    }

    public function setAiModel(string $aiModel): void
    {
        $this->aiModel = $aiModel;
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

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): void
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }
    }

    public function removeMessage(Message $message): void
    {
        $this->messages->removeElement($message);
    }

    public function getMessageCount(): int
    {
        return $this->messages->count();
    }
}
