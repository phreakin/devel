<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private Conversation $conversation;
    private Message $message;

    protected function setUp(): void
    {
        $user = new User('test@example.com', 'password', 'Test User');
        $this->conversation = new Conversation($user, 'Test Conversation');
        $this->message = new Message($this->conversation, 'user', 'Hello, AI!');
    }

    public function testMessageCanBeCreated(): void
    {
        $this->assertNotEmpty($this->message->getId());
        $this->assertSame('user', $this->message->getRole());
        $this->assertSame('Hello, AI!', $this->message->getContent());
        $this->assertSame($this->conversation, $this->message->getConversation());
    }

    public function testMessageCanBeFromUser(): void
    {
        $this->assertTrue($this->message->isFromUser());
        $this->assertFalse($this->message->isFromAssistant());
    }

    public function testMessageCanBeFromAssistant(): void
    {
        $assistantMessage = new Message($this->conversation, 'assistant', 'Hello, user!');
        $this->assertTrue($assistantMessage->isFromAssistant());
        $this->assertFalse($assistantMessage->isFromUser());
    }

    public function testMessageContentCanBeUpdated(): void
    {
        $this->message->setContent('Updated content');
        $this->assertSame('Updated content', $this->message->getContent());
    }

    public function testMessageRoleCanBeUpdated(): void
    {
        $this->message->setRole('assistant');
        $this->assertSame('assistant', $this->message->getRole());
    }

    public function testMessageCanHaveMetadata(): void
    {
        $metadata = json_encode(['tokens' => 150, 'model' => 'gpt-4']);
        $this->message->setMetadata($metadata);
        $this->assertSame($metadata, $this->message->getMetadata());
    }

    public function testMessageHasTimestamps(): void
    {
        $this->assertNotNull($this->message->getCreatedAt());
        $this->assertNotNull($this->message->getUpdatedAt());
    }
}
