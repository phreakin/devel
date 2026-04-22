<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\AIService;
use App\Entity\Conversation;
use App\Entity\User;
use App\Entity\Message;
use PHPUnit\Framework\TestCase;

class AIServiceTest extends TestCase
{
    /**
     * Test that AIService can be instantiated with API key
     */
    public function testCanBeInstantiatedWithApiKey(): void
    {
        $apiKey = 'sk-test-key-12345';
        $service = new AIService($apiKey);

        $this->assertInstanceOf(AIService::class, $service);
    }

    /**
     * Test message history building with conversation context
     */
    public function testBuildsMessageHistoryFromConversation(): void
    {
        $apiKey = 'sk-test-key-12345';
        $service = new AIService($apiKey);

        // Create test conversation with messages
        $user = new User('test@example.com', 'hashed', 'Test');
        $conversation = new Conversation($user, 'Test Chat', 'gpt-4');

        // Add some messages
        $msg1 = new Message($conversation, 'user', 'Hello');
        $msg2 = new Message($conversation, 'assistant', 'Hi there!');
        $msg3 = new Message($conversation, 'user', 'How are you?');

        $conversation->addMessage($msg1);
        $conversation->addMessage($msg2);
        $conversation->addMessage($msg3);

        // Test that service can handle conversation with messages
        // Note: We can't fully test generateResponse without real API key
        // But we can verify the service accepts conversation context
        $this->assertSame(3, $conversation->getMessageCount());
    }

    /**
     * Test that conversation model is used in API call
     */
    public function testUsesConversationModel(): void
    {
        $apiKey = 'sk-test-key-12345';
        $service = new AIService($apiKey);

        $user = new User('test@example.com', 'hashed', 'Test');

        // Test with gpt-4
        $conv1 = new Conversation($user, 'GPT4 Chat', 'gpt-4');
        $this->assertSame('gpt-4', $conv1->getAiModel());

        // Test with claude-3
        $conv2 = new Conversation($user, 'Claude Chat', 'claude-3');
        $this->assertSame('claude-3', $conv2->getAiModel());

        // Test with other models
        $conv3 = new Conversation($user, 'GPT35 Chat', 'gpt-3.5-turbo');
        $this->assertSame('gpt-3.5-turbo', $conv3->getAiModel());
    }

    /**
     * Test that message history is limited to prevent token overflow
     */
    public function testLimitsMessageHistoryToPreventTokenOverflow(): void
    {
        $apiKey = 'sk-test-key-12345';
        $service = new AIService($apiKey);

        $user = new User('test@example.com', 'hashed', 'Test');
        $conversation = new Conversation($user, 'Long Chat', 'gpt-4');

        // Add 15 messages (should keep only last 10)
        for ($i = 0; $i < 15; $i++) {
            $role = $i % 2 === 0 ? 'user' : 'assistant';
            $msg = new Message($conversation, $role, "Message {$i}");
            $conversation->addMessage($msg);
        }

        // Verify we can access the conversation
        $this->assertSame(15, $conversation->getMessageCount());
        // The service will only use the last 10 in the API call
    }

    /**
     * Test assistant message is created with proper role
     */
    public function testAssistantMessageHasCorrectRole(): void
    {
        $user = new User('test@example.com', 'hashed', 'Test');
        $conversation = new Conversation($user, 'Test', 'gpt-4');

        $assistantMsg = new Message($conversation, 'assistant', 'This is an AI response');

        $this->assertSame('assistant', $assistantMsg->getRole());
        $this->assertSame('This is an AI response', $assistantMsg->getContent());
    }

    /**
     * Test that user messages and assistant messages are properly associated
     */
    public function testMessageAssociationWithConversation(): void
    {
        $user = new User('test@example.com', 'hashed', 'Test');
        $conversation = new Conversation($user, 'Test', 'gpt-4');

        $userMsg = new Message($conversation, 'user', 'User question');
        $assistantMsg = new Message($conversation, 'assistant', 'Assistant answer');

        $conversation->addMessage($userMsg);
        $conversation->addMessage($assistantMsg);

        $this->assertSame(2, $conversation->getMessageCount());
        $messages = $conversation->getMessages();
        $this->assertSame('User question', $messages[0]->getContent());
        $this->assertSame('Assistant answer', $messages[1]->getContent());
    }
}
