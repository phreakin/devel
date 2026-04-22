<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\ApiResponse;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $response = ApiResponse::success(['id' => '123', 'name' => 'Test']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Success', $response->getMessage());
        $this->assertSame(['id' => '123', 'name' => 'Test'], $response->getData());
    }

    public function testCreatedResponse(): void
    {
        $response = ApiResponse::created(['id' => '456']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created', $response->getMessage());
    }

    public function testNoContentResponse(): void
    {
        $response = ApiResponse::noContent();

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testBadRequestResponse(): void
    {
        $response = ApiResponse::badRequest('Invalid input', ['email' => 'Email is required']);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid input', $response->getMessage());
        $this->assertSame(['email' => 'Email is required'], $response->getErrors());
    }

    public function testUnauthorizedResponse(): void
    {
        $response = ApiResponse::unauthorized('Invalid credentials');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testNotFoundResponse(): void
    {
        $response = ApiResponse::notFound('Resource not found');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testToJsonOutput(): void
    {
        $response = ApiResponse::success(['id' => '123'], 200, 'OK');
        $json = $response->toJson();
        $decoded = json_decode($json, true);

        $this->assertTrue($decoded['success']);
        $this->assertSame('OK', $decoded['message']);
        $this->assertSame(['id' => '123'], $decoded['data']);
    }

    public function testErrorResponseToJson(): void
    {
        $response = ApiResponse::badRequest('Validation failed', ['email' => 'Invalid']);
        $json = $response->toJson();
        $decoded = json_decode($json, true);

        $this->assertFalse($decoded['success']);
        $this->assertSame(['email' => 'Invalid'], $decoded['errors']);
    }
}
