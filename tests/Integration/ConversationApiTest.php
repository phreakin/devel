<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\ApiApplication;
use App\Entity\User;
use App\Http\Request;

class ConversationApiTest extends DatabaseTestCase
{
    private ApiApplication $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new ApiApplication($this->entityManager);

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

    public function testCanCreateConversationViaApi(): void
    {
        $request = $this->createJsonRequest('POST', '/api/conversations', [
            'title' => 'First Conversation',
            'aiModel' => 'gpt-4',
            'description' => 'Testing the API',
        ]);

        $response = $this->app->handleRequest($request);

        $this->assertTrue($response->isSuccess());
        $data = $response->getData();
        $this->assertNotEmpty($data['id']);
        $this->assertSame('First Conversation', $data['title']);
        $this->assertSame('gpt-4', $data['aiModel']);
    }

    public function testCanListConversationsViaApi(): void
    {
        // Create test data first
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($user);

        $request1 = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Chat 1']);
        $this->app->handleRequest($request1);

        $request2 = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Chat 2']);
        $this->app->handleRequest($request2);

        // Now list them
        $listRequest = new Request('GET', '/api/conversations');
        $response = $this->app->handleRequest($listRequest);

        $this->assertTrue($response->isSuccess());
        $data = $response->getData();
        $this->assertCount(2, $data['conversations']);
        $this->assertSame('Chat 1', $data['conversations'][0]['title']);
        $this->assertSame('Chat 2', $data['conversations'][1]['title']);
    }

    public function testCanGetConversationViaApi(): void
    {
        // Create conversation first
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Test Chat']);
        $createResponse = $this->app->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Get it
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}", ['id' => $conversationId]);
        $getResponse = $this->app->handleRequest($getRequest);

        $this->assertTrue($getResponse->isSuccess());
        $data = $getResponse->getData();
        $this->assertSame('Test Chat', $data['conversation']['title']);
        $this->assertSame(0, $data['conversation']['messageCount']);
    }

    public function testCanAddMessageViaApi(): void
    {
        // Create conversation first
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Message Test Chat']);
        $createResponse = $this->app->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Add a message
        $msgRequest = $this->createJsonRequest(
            'POST',
            "/api/conversations/{$conversationId}/messages",
            [
                'role' => 'user',
                'content' => 'Hello AI!',
                'metadata' => ['model' => 'gpt-4'],
            ],
            ['conversationId' => $conversationId]
        );

        $msgResponse = $this->app->handleRequest($msgRequest);

        $this->assertTrue($msgResponse->isSuccess());
        $data = $msgResponse->getData();
        $this->assertNotEmpty($data['id']);
        $this->assertSame('user', $data['role']);
        $this->assertSame('Hello AI!', $data['content']);
    }

    public function testCanGetMessagesViaApi(): void
    {
        // Create conversation with messages
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Messages Test']);
        $createResponse = $this->app->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Add 2 messages
        for ($i = 1; $i <= 2; $i++) {
            $msgRequest = $this->createJsonRequest(
                'POST',
                "/api/conversations/{$conversationId}/messages",
                [
                    'role' => $i === 1 ? 'user' : 'assistant',
                    'content' => "Message {$i}",
                ],
                ['conversationId' => $conversationId]
            );
            $this->app->handleRequest($msgRequest);
        }

        // Get messages
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}/messages", ['conversationId' => $conversationId]);
        $getResponse = $this->app->handleRequest($getRequest);

        $this->assertTrue($getResponse->isSuccess());
        $data = $getResponse->getData();
        $this->assertCount(2, $data['messages']);
        $this->assertSame('Message 1', $data['messages'][0]['content']);
        $this->assertSame('Message 2', $data['messages'][1]['content']);
    }

    public function testCanUpdateConversationViaApi(): void
    {
        // Create conversation
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'Original Title']);
        $createResponse = $this->app->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Update it
        $updateRequest = $this->createJsonRequest(
            'PUT',
            "/api/conversations/{$conversationId}",
            [
                'title' => 'Updated Title',
                'aiModel' => 'claude-3',
            ],
            ['id' => $conversationId]
        );

        $updateResponse = $this->app->handleRequest($updateRequest);

        $this->assertTrue($updateResponse->isSuccess());
        $data = $updateResponse->getData();
        $this->assertSame('Updated Title', $data['conversation']['title']);
        $this->assertSame('claude-3', $data['conversation']['aiModel']);
    }

    public function testCanDeleteConversationViaApi(): void
    {
        // Create conversation
        $request = $this->createJsonRequest('POST', '/api/conversations', ['title' => 'To Delete']);
        $createResponse = $this->app->handleRequest($request);
        $conversationId = $createResponse->getData()['id'];

        // Delete it
        $deleteRequest = new Request('DELETE', "/api/conversations/{$conversationId}", ['id' => $conversationId]);
        $deleteResponse = $this->app->handleRequest($deleteRequest);

        $this->assertTrue($deleteResponse->isSuccess());

        // Verify it's gone
        $getRequest = new Request('GET', "/api/conversations/{$conversationId}", ['id' => $conversationId]);
        $getResponse = $this->app->handleRequest($getRequest);

        $this->assertFalse($getResponse->isSuccess());
        $this->assertStringContainsString('not found', strtolower($getResponse->getMessage()));
    }
}
