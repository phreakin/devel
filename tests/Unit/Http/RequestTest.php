<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        $this->request = new Request(
            'POST',
            '/api/conversations',
            [],
            [],
            json_encode(['title' => 'New Chat']),
            ['Content-Type' => 'application/json']
        );
    }

    public function testRequestMethod(): void
    {
        $this->assertSame('POST', $this->request->getMethod());
    }

    public function testRequestPath(): void
    {
        $this->assertSame('/api/conversations', $this->request->getPath());
    }

    public function testRequestBody(): void
    {
        $this->assertIsString($this->request->getBody());
    }

    public function testRequestJsonBody(): void
    {
        $json = $this->request->getJsonBody();
        $this->assertSame('New Chat', $json['title']);
    }

    public function testRequestParams(): void
    {
        $this->request->setParams(['id' => '123']);
        $this->assertSame('123', $this->request->getParam('id'));
    }

    public function testRequestHeaders(): void
    {
        $this->assertTrue($this->request->hasHeader('Content-Type'));
        $this->assertStringContainsString('application/json', $this->request->getHeader('Content-Type'));
    }

    public function testRequestIsJson(): void
    {
        $this->assertTrue($this->request->isJson());
    }

    public function testRequestQuery(): void
    {
        $request = new Request('GET', '/api/conversations', [], ['limit' => '10']);
        $this->assertSame('10', $request->getQuery('limit'));
    }
}
