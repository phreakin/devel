<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;

class ConversationRepositoryTest extends DatabaseTestCase
{
    private ConversationRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = new User('test@example.com', 'hashed_password', 'Test User');
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        $this->repository = new ConversationRepository($this->entityManager);
    }

    public function testCanCreateAndRetrieveConversation(): void
    {
        $conversation = new Conversation($this->user, 'Test Conversation', 'gpt-4');
        $conversation->setDescription('A test conversation');

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $retrieved = $this->repository->findById($conversation->getId());

        $this->assertNotNull($retrieved);
        $this->assertSame('Test Conversation', $retrieved->getTitle());
        $this->assertSame('gpt-4', $retrieved->getAiModel());
        $this->assertSame('A test conversation', $retrieved->getDescription());
    }

    public function testCanListUserConversations(): void
    {
        $conv1 = new Conversation($this->user, 'First Chat', 'gpt-4');
        $conv2 = new Conversation($this->user, 'Second Chat', 'claude-3');

        $this->entityManager->persist($conv1);
        $this->entityManager->persist($conv2);
        $this->entityManager->flush();

        $conversations = $this->repository->findByUser($this->user);

        $this->assertCount(2, $conversations);
        $this->assertSame('First Chat', $conversations[0]->getTitle());
        $this->assertSame('Second Chat', $conversations[1]->getTitle());
    }

    public function testCanListActiveConversations(): void
    {
        $activeConv = new Conversation($this->user, 'Active Chat', 'gpt-4');
        $inactiveConv = new Conversation($this->user, 'Archived Chat', 'gpt-4');
        $inactiveConv->setActive(false);

        $this->entityManager->persist($activeConv);
        $this->entityManager->persist($inactiveConv);
        $this->entityManager->flush();

        $activeConversations = $this->repository->findActiveByUser($this->user);

        $this->assertCount(1, $activeConversations);
        $this->assertSame('Active Chat', $activeConversations[0]->getTitle());
    }

    public function testConversationCanContainMessages(): void
    {
        $conversation = new Conversation($this->user, 'Chat with Messages', 'gpt-4');

        $userMsg = new Message($conversation, 'user', 'Hello AI!');
        $aiMsg = new Message($conversation, 'assistant', 'Hello human!');

        $conversation->addMessage($userMsg);
        $conversation->addMessage($aiMsg);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $retrieved = $this->repository->findById($conversation->getId());

        $this->assertCount(2, $retrieved->getMessages());
        $this->assertSame(2, $retrieved->getMessageCount());
    }

    public function testCanUpdateConversation(): void
    {
        $conversation = new Conversation($this->user, 'Original Title', 'gpt-4');
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $conversation->setTitle('Updated Title');
        $conversation->setAiModel('claude-3');
        $this->entityManager->flush();

        $retrieved = $this->repository->findById($conversation->getId());

        $this->assertSame('Updated Title', $retrieved->getTitle());
        $this->assertSame('claude-3', $retrieved->getAiModel());
    }

    public function testCanDeleteConversationWithCascade(): void
    {
        $conversation = new Conversation($this->user, 'To Delete', 'gpt-4');
        $message = new Message($conversation, 'user', 'Message in deleted conversation');
        $conversation->addMessage($message);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        $conversationId = $conversation->getId();
        $messageId = $message->getId();

        // Delete conversation
        $this->entityManager->remove($conversation);
        $this->entityManager->flush();

        // Verify both are gone (cascade delete)
        $this->assertNull($this->repository->findById($conversationId));
        $this->assertNull($this->entityManager->find(Message::class, $messageId));
    }
}
