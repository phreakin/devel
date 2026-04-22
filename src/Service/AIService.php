<?php

declare(strict_types=1);

namespace App\Service;

use OpenAI\Client;
use App\Entity\Conversation;
use App\Entity\Message;

class AIService
{
    private Client $client;

    public function __construct(string $apiKey)
    {
        $this->client = \OpenAI::client($apiKey);
    }

    /**
     * Generate AI response for a conversation
     *
     * @param Conversation $conversation The conversation context
     * @param string $userMessage The user's message to respond to
     * @return string The AI's response text
     * @throws \Exception If API call fails
     */
    public function generateResponse(Conversation $conversation, string $userMessage): string
    {
        // Prepare conversation history for the API
        $messages = $this->buildMessageHistory($conversation, $userMessage);

        try {
            $response = $this->client->chat()->create([
                'model' => $conversation->getAiModel(),
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            throw new \Exception("Failed to generate AI response: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build message history for API call
     * Includes recent conversation messages to provide context
     *
     * @param Conversation $conversation
     * @param string $newUserMessage
     * @return array
     */
    private function buildMessageHistory(Conversation $conversation, string $newUserMessage): array
    {
        $messages = [];

        // Add recent messages from conversation (last 10 to limit tokens)
        $conversationMessages = $conversation->getMessages()->toArray();
        $recentMessages = array_slice($conversationMessages, -10);

        foreach ($recentMessages as $msg) {
            $messages[] = [
                'role' => $msg->getRole(),
                'content' => $msg->getContent(),
            ];
        }

        // Add the new user message
        $messages[] = [
            'role' => 'user',
            'content' => $newUserMessage,
        ];

        return $messages;
    }

    /**
     * Test API connectivity
     *
     * @return bool True if API is reachable
     */
    public function testConnection(): bool
    {
        try {
            $this->client->models()->list();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
