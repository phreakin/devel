<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\Message;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    private User $user;
    private Conversation $conversation;

    protected function setUp(): void
    {
        $this->user = new User('test@example.com', 'password', 'Test User');
        $this->conversation = new Conversation($this->user, 'Test Conversation', 'gpt-4');
    }

    public function testConversationCanBeCreated(): void
    {
        $this->assertNotEmpty($this->conversation->getId());
        $this->assertSame('Test Conversation', $this->conversation->getTitle());
        $this->assertSame('gpt-4', $this->conversation->getAiModel());
        $this->assertSame($this->user, $this->conversation->getUser());
    }

    public function testConversationIsActiveByDefault(): void
    {
        $this->assertTrue($this->conversation->isActive());
    }

    public function testConversationTitleCanBeUpdated(): void
    {
        $this->conversation->setTitle('Updated Title');
        $this->assertSame('Updated Title', $this->conversation->getTitle());
    }

    public function testConversationAiModelCanBeUpdated(): void
    {
        $this->conversation->setAiModel('claude-3');
        $this->assertSame('claude-3', $this->conversation->getAiModel());
    }

    public function testConversationHasMessages(): void
    {
        $this->assertCount(0, $this->conversation->getMessages());
    }

    public function testConversationCanAddMessage(): void
    {
        $message = new Message($this->conversation, 'user', 'Hello');
        $this->conversation->addMessage($message);

        $this->assertCount(1, $this->conversation->getMessages());
        $this->assertSame(1, $this->conversation->getMessageCount());
    }

    public function testConversationHasTimestamps(): void
    {
        $this->assertNotNull($this->conversation->getCreatedAt());
        $this->assertNotNull($this->conversation->getUpdatedAt());
    }
}
