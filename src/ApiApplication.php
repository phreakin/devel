<?php

declare(strict_types=1);

namespace App;

use App\Entity\User;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Http\Request;
use App\Http\Router;
use App\Http\ApiResponse;
use App\Http\AuthMiddleware;
use App\Repository\UserRepository;
use App\Repository\ConversationRepository;
use App\Service\AIService;
use App\Service\AuthService;
use Doctrine\ORM\EntityManager;
use FastRoute\RouteCollector;

class ApiApplication
{
    private EntityManager $entityManager;
    private Router $router;
    private ?AIService $aiService = null;
    private ?AuthService $authService = null;
    private ?AuthMiddleware $authMiddleware = null;

    public function __construct(EntityManager $entityManager, ?string $openaiApiKey = null, ?string $jwtSecret = null)
    {
        $this->entityManager = $entityManager;
        if ($openaiApiKey) {
            $this->aiService = new AIService($openaiApiKey);
        }
        if ($jwtSecret) {
            $this->authService = new AuthService($jwtSecret);
            $this->authMiddleware = new AuthMiddleware($this->authService);
        }
        $this->router = new Router($this->defineRoutes(...));
    }

    private function resolveUserId(Request $request): ?string
    {
        if ($this->authMiddleware) {
            return $this->authMiddleware->authenticate($request);
        }

        $userRepository = new UserRepository($this->entityManager);
        $user = $userRepository->findAll()[0] ?? null;

        return $user?->getId();
    }

    private function defineRoutes(RouteCollector $r): void
    {
        // Health check
        $r->get('/api/health', $this->healthCheck(...));

        // Auth routes
        $r->post('/api/auth/register', $this->register(...));
        $r->post('/api/auth/login', $this->login(...));

        // Conversation routes
        $r->post('/api/conversations', $this->createConversation(...));
        $r->get('/api/conversations', $this->listConversations(...));
        $r->get('/api/conversations/{id}', $this->getConversation(...));
        $r->put('/api/conversations/{id}', $this->updateConversation(...));
        $r->delete('/api/conversations/{id}', $this->deleteConversation(...));

        // Message routes
        $r->post('/api/conversations/{conversationId}/messages', $this->addMessage(...));
        $r->get('/api/conversations/{conversationId}/messages', $this->getMessages(...));
    }

    public function handleRequest(Request $request): ApiResponse
    {
        $match = $this->router->dispatch($request);

        if (!$match->isFound()) {
            return ApiResponse::notFound('Endpoint not found');
        }

        $handler = $match->getHandler();
        $request->setParams($match->getParams());

        try {
            return $handler($request);
        } catch (\Throwable $e) {
            return ApiResponse::internalError($e->getMessage());
        }
    }

    // Handlers

    private function healthCheck(Request $request): ApiResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'timestamp' => date('c'),
        ]);
    }

    private function register(Request $request): ApiResponse
    {
        if (!$this->authService) {
            return ApiResponse::badRequest('Authentication not configured');
        }

        $data = $request->getJsonBody();

        if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
            return ApiResponse::badRequest('Missing required fields: email, password, name');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::badRequest('Invalid email format');
        }

        if (strlen($data['password']) < 8) {
            return ApiResponse::badRequest('Password must be at least 8 characters');
        }

        // Check if user already exists
        $userRepository = new UserRepository($this->entityManager);
        $existingUser = $userRepository->findByEmail($data['email']);
        
        if ($existingUser) {
            return ApiResponse::conflict('Email already registered');
        }

        // Hash password and create user
        $hashedPassword = $this->authService->hashPassword($data['password']);
        $user = new User($data['email'], $hashedPassword, $data['name']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return ApiResponse::created([
            'message' => 'User registered successfully. Please login.',
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
        ], 'User registered successfully. Please login.');
    }

    private function login(Request $request): ApiResponse
    {
        if (!$this->authService) {
            return ApiResponse::badRequest('Authentication not configured');
        }

        $data = $request->getJsonBody();

        if (empty($data['email']) || empty($data['password'])) {
            return ApiResponse::badRequest('Missing required fields: email, password');
        }

        // Find user by email
        $userRepository = new UserRepository($this->entityManager);
        $user = $userRepository->findByEmail($data['email']);

        if (!$user) {
            return ApiResponse::unauthorized('Invalid email or password');
        }

        // Verify password
        if (!$this->authService->verifyPassword($data['password'], $user->getPassword())) {
            return ApiResponse::unauthorized('Invalid email or password');
        }

        // Check if user is active
        if (!$user->isActive()) {
            return ApiResponse::unauthorized('User account is inactive');
        }

        // Generate JWT token
        $token = $this->authService->generateToken($user);

        return ApiResponse::success([
            'message' => 'Login successful',
            'token' => $token,
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ], ApiResponse::HTTP_OK, 'Login successful');
    }

    private function createConversation(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $userRepository = new UserRepository($this->entityManager);
        $user = $userRepository->findById($userId);

        if (!$user) {
            return ApiResponse::unauthorized('User not found');
        }

        $data = $request->getJsonBody();

        if (empty($data['title'])) {
            return ApiResponse::badRequest('Title is required');
        }

        $aiModel = $data['aiModel'] ?? 'gpt-4';
        $description = $data['description'] ?? null;

        $conversation = new Conversation($user, $data['title'], $aiModel);
        if ($description) {
            $conversation->setDescription($description);
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return ApiResponse::created([
            'message' => 'Conversation created successfully',
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'aiModel' => $conversation->getAiModel(),
            'createdAt' => $conversation->getCreatedAt()->format('c'),
        ], 'Conversation created successfully');
    }

    private function listConversations(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $userRepository = new UserRepository($this->entityManager);
        $user = $userRepository->findById($userId);

        if (!$user) {
            return ApiResponse::unauthorized('User not found');
        }

        $repository = new ConversationRepository($this->entityManager);
        $conversations = $repository->findActiveByUser($user);

        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'title' => $c->getTitle(),
            'aiModel' => $c->getAiModel(),
            'description' => $c->getDescription(),
            'messageCount' => $c->getMessageCount(),
            'createdAt' => $c->getCreatedAt()->format('c'),
            'updatedAt' => $c->getUpdatedAt()->format('c'),
        ], $conversations);

        return ApiResponse::success([
            'conversations' => $data,
            'total' => count($data),
        ]);
    }

    private function getConversation(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $conversationId = $request->getParam('id');

        $repository = new ConversationRepository($this->entityManager);
        $conversation = $repository->findById($conversationId);

        if (!$conversation) {
            return ApiResponse::notFound('Conversation not found');
        }

        // Verify ownership
        if ($conversation->getUser()->getId() !== $userId) {
            return ApiResponse::forbidden('You do not have access to this conversation');
        }

        return ApiResponse::success([
            'conversation' => [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'description' => $conversation->getDescription(),
                'aiModel' => $conversation->getAiModel(),
                'active' => $conversation->isActive(),
                'messageCount' => $conversation->getMessageCount(),
                'createdAt' => $conversation->getCreatedAt()->format('c'),
                'updatedAt' => $conversation->getUpdatedAt()->format('c'),
            ],
        ]);
    }

    private function updateConversation(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $conversationId = $request->getParam('id');
        $data = $request->getJsonBody();

        $repository = new ConversationRepository($this->entityManager);
        $conversation = $repository->findById($conversationId);

        if (!$conversation) {
            return ApiResponse::notFound('Conversation not found');
        }

        // Verify ownership
        if ($conversation->getUser()->getId() !== $userId) {
            return ApiResponse::forbidden('You do not have access to this conversation');
        }

        if (isset($data['title'])) {
            $conversation->setTitle($data['title']);
        }

        if (isset($data['aiModel'])) {
            $conversation->setAiModel($data['aiModel']);
        }

        if (isset($data['description'])) {
            $conversation->setDescription($data['description']);
        }

        if (isset($data['active'])) {
            $conversation->setActive((bool)$data['active']);
        }

        $this->entityManager->flush();

        return ApiResponse::success([
            'message' => 'Conversation updated successfully',
            'conversation' => [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'aiModel' => $conversation->getAiModel(),
                'description' => $conversation->getDescription(),
                'active' => $conversation->isActive(),
                'updatedAt' => $conversation->getUpdatedAt()->format('c'),
            ],
        ]);
    }

    private function deleteConversation(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $conversationId = $request->getParam('id');

        $repository = new ConversationRepository($this->entityManager);
        $conversation = $repository->findById($conversationId);

        if (!$conversation) {
            return ApiResponse::notFound('Conversation not found');
        }

        // Verify ownership
        if ($conversation->getUser()->getId() !== $userId) {
            return ApiResponse::forbidden('You do not have access to this conversation');
        }

        $this->entityManager->remove($conversation);
        $this->entityManager->flush();

        return ApiResponse::noContent();
    }

    private function addMessage(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $conversationId = $request->getParam('conversationId');
        $data = $request->getJsonBody();

        if (empty($data['content'])) {
            return ApiResponse::badRequest('Content is required');
        }

        $role = $data['role'] ?? 'user';
        if (!in_array($role, ['user', 'assistant'])) {
            return ApiResponse::badRequest('Invalid role. Must be "user" or "assistant"');
        }

        // TODO: Verify authentication and conversation ownership
        $repository = new ConversationRepository($this->entityManager);
        $conversation = $repository->findById($conversationId);

        if (!$conversation) {
            return ApiResponse::notFound('Conversation not found');
        }

        $message = new Message($conversation, $role, $data['content']);
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $message->setMetadata(json_encode($data['metadata'], JSON_THROW_ON_ERROR));
        }

        $conversation->addMessage($message);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // If user message and AI service available, generate AI response
        if ($role === 'user' && $this->aiService !== null) {
            try {
                $aiResponse = $this->aiService->generateResponse($conversation, $data['content']);

                // Create and persist assistant message
                $assistantMessage = new Message($conversation, 'assistant', $aiResponse);
                $conversation->addMessage($assistantMessage);
                $this->entityManager->persist($assistantMessage);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                // Log error but don't fail the request - user message was saved
                error_log("AI Service error: " . $e->getMessage());
            }
        }

        return ApiResponse::created([
            'message' => 'Message added successfully',
            'id' => $message->getId(),
            'role' => $message->getRole(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('c'),
        ]);
    }

    private function getMessages(Request $request): ApiResponse
    {
        try {
            $userId = $this->resolveUserId($request);
        } catch (\Exception $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        if (!$userId) {
            return ApiResponse::unauthorized('Authentication required');
        }

        $conversationId = $request->getParam('conversationId');
        $limit = (int)($request->getQuery('limit') ?? 50);
        $offset = (int)($request->getQuery('offset') ?? 0);

        // Validate limits
        if ($limit < 1 || $limit > 200) {
            $limit = 50;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        $repository = new ConversationRepository($this->entityManager);
        $conversation = $repository->findById($conversationId);

        if (!$conversation) {
            return ApiResponse::notFound('Conversation not found');
        }

        // Verify ownership
        if ($conversation->getUser()->getId() !== $userId) {
            return ApiResponse::forbidden('You do not have access to this conversation');
        }

        $messages = $conversation->getMessages();
        $data = array_map(fn($m) => [
            'id' => $m->getId(),
            'role' => $m->getRole(),
            'content' => $m->getContent(),
            'metadata' => $m->getMetadata() ? json_decode($m->getMetadata(), true) : null,
            'createdAt' => $m->getCreatedAt()->format('c'),
        ], $messages->toArray());

        return ApiResponse::success([
            'messages' => $data,
            'total' => count($data),
        ]);
    }
}
