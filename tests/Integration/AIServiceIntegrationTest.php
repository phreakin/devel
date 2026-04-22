<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\ApiApplication;
use App\Entity\User;
use App\Http\Request;

class AIServiceIntegrationTest extends DatabaseTestCase
{
    private ApiApplication $appWithoutAI;
    private ApiApplication $appWithAI;

    protected function setUp(): void
    {
        parent::setUp();

        // App without AI service (no API key)
        $this->appWithoutAI = new ApiApplication($this->entityManager);

        // App with AI service (fake API key for testing)
        $this->appWithAI = new ApiApplication($this->entityManager, 'sk-test-key-12345');

        // Create a test user for API tests
        $user = new User('test@example.com', 'hashed_password', 'Test User');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function createJsonRequest(string $method, string $path, array $body = [], array $params = []): Request
    {
        $bodyJson = json_encode($body);
        return new Request(
            $method,
            $path,
            $params,
            [],
            $bodyJson,
            ['content-type' => 'application/json']
        );
    }

    /**
     * Test that API works without AI service (graceful degradation)
     */
    public function testApiWorksWithoutAIService(): void
    {
        // Create conversation
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'No AI Chat']);
        $createResponse = $this->appWithoutAI->handleRequest($request);
        $this->assertTrue($createResponse->isSuccess());

        $conversationId = $createResponse->getData()['id'];

        // Add message without AI service
        $msgRequest = $this->createJsonRequest(
            'POST',
            "/api/conversations/{$conversationId}/messages",
            ['role' => 'user', 'content' => 'Hello!'],
            ['conversationId' => $conversationId]
        );

        $msgResponse = $this->appWithoutAI->handleRequest($msgRequest);

        // Should succeed - user message saved, no AI response attempted
        $this->assertTrue($msgResponse->isSuccess());

        // Get messages - only user message should exist
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}/messages", ['conversationId' => $conversationId]);
        $getResponse = $this->appWithoutAI->handleRequest($getRequest);

        $data = $getResponse->getData();
        $this->assertCount(1, $data['messages']);
        $this->assertSame('user', $data['messages'][0]['role']);
    }

    /**
     * Test that conversation maintains AI model preference
     */
    public function testConversationMaintainsAIModelPreference(): void
    {
        // Create conversation with specific model
        $request = $this->createJsonRequest(
            'POST',
            '/api/conversations',
            [
                'title' => 'Claude Chat',
                'aiModel' => 'claude-3-opus',
            ]
        );

        $createResponse = $this->appWithAI->handleRequest($request);
        $this->assertTrue($createResponse->isSuccess());

        $data = $createResponse->getData();
        $this->assertSame('claude-3-opus', $data['aiModel']);

        $conversationId = $data['id'];

        // Verify it's stored correctly
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}", ['id' => $conversationId]);
        $getResponse = $this->appWithAI->handleRequest($getRequest);

        $convData = $getResponse->getData();
        $this->assertSame('claude-3-opus', $convData['conversation']['aiModel']);
    }

    /**
     * Test conversation history is available for AI context
     */
    public function testConversationHistoryAvailableForContext(): void
    {
        // Create conversation
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'History Test']);
        $createResponse = $this->appWithoutAI->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Add multiple messages
        $messages = [
            ['role' => 'user', 'content' => 'What is 2+2?'],
            ['role' => 'assistant', 'content' => 'The answer is 4.'],
            ['role' => 'user', 'content' => 'What about 3+3?'],
        ];

        foreach ($messages as $msg) {
            $msgRequest = $this->createJsonRequest(
                'POST',
                "/api/conversations/{$conversationId}/messages",
                $msg,
                ['conversationId' => $conversationId]
            );
            $this->appWithoutAI->handleRequest($msgRequest);
        }

        // Get all messages
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}/messages", ['conversationId' => $conversationId]);
        $getResponse = $this->appWithoutAI->handleRequest($getRequest);

        $data = $getResponse->getData();
        $this->assertCount(3, $data['messages']);

        // Verify message content
        $this->assertSame('What is 2+2?', $data['messages'][0]['content']);
        $this->assertSame('The answer is 4.', $data['messages'][1]['content']);
        $this->assertSame('What about 3+3?', $data['messages'][2]['content']);
    }

    /**
     * Test message roles are preserved for AI context
     */
    public function testMessageRolesArePreserved(): void
    {
        // Create conversation
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Role Test']);
        $createResponse = $this->appWithoutAI->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Add user message
        $userMsg = $this->createJsonRequest(
            'POST',
            "/api/conversations/{$conversationId}/messages",
            ['role' => 'user', 'content' => 'User asks something'],
            ['conversationId' => $conversationId]
        );
        $this->appWithoutAI->handleRequest($userMsg);

        // Add assistant message
        $assistantMsg = $this->createJsonRequest(
            'POST',
            "/api/conversations/{$conversationId}/messages",
            ['role' => 'assistant', 'content' => 'AI responds'],
            ['conversationId' => $conversationId]
        );
        $this->appWithoutAI->handleRequest($assistantMsg);

        // Verify roles in retrieved messages
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}/messages", ['conversationId' => $conversationId]);
        $getResponse = $this->appWithoutAI->handleRequest($getRequest);

        $data = $getResponse->getData();
        $this->assertSame('user', $data['messages'][0]['role']);
        $this->assertSame('assistant', $data['messages'][1]['role']);
    }

    /**
     * Test metadata is preserved in messages for AI context
     */
    public function testMessageMetadataIsPreserved(): void
    {
        // Create conversation
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Metadata Test']);
        $createResponse = $this->appWithoutAI->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Add message with metadata
        $msgRequest = $this->createJsonRequest(
            'POST',
            "/api/conversations/{$conversationId}/messages",
            [
                'role' => 'user',
                'content' => 'Question with context',
                'metadata' => [
                    'model' => 'gpt-4',
                    'tokens_used' => 150,
                    'temperature' => 0.7,
                ],
            ],
            ['conversationId' => $conversationId]
        );

        $msgResponse = $this->appWithoutAI->handleRequest($msgRequest);
        $this->assertTrue($msgResponse->isSuccess());

        // Verify metadata is retrieved
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}/messages", ['conversationId' => $conversationId]);
        $getResponse = $this->appWithoutAI->handleRequest($getRequest);

        $data = $getResponse->getData();
        $this->assertSame('gpt-4', $data['messages'][0]['metadata']['model']);
        $this->assertSame(150, $data['messages'][0]['metadata']['tokens_used']);
        $this->assertSame(0.7, $data['messages'][0]['metadata']['temperature']);
    }
}
