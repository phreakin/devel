<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: 'messages')]
class Message
{
    #[Id]
    #[Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[JoinColumn(name: 'conversationId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[Column(type: Types::STRING, length: 10, options: ['default' => 'user'])]
    private string $role = 'user';

    #[Column(type: Types::TEXT)]
    private string $content;

    #[Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $updatedAt;

    public function __construct(Conversation $conversation, string $role, string $content)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->conversation = $conversation;
        $this->role = $role;
        $this->content = $content;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation): void
    {
        $this->conversation = $conversation;
        $this->updatedAt = new DateTime();
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
        $this->updatedAt = new DateTime();
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->updatedAt = new DateTime();
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setMetadata(?string $metadata): void
    {
        $this->metadata = $metadata;
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

    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
