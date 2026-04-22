<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    private string $method;
    private string $path;
    private array $params = [];
    private array $queryParams = [];
    private ?string $body = null;
    private array $headers = [];

    public function __construct(string $method, string $path, array $params = [], array $queryParams = [], ?string $body = null, array $headers = [])
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->params = $params;
        $this->queryParams = $queryParams;
        $this->body = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function fromGlobal(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $queryParams = $_GET ?? [];
        $body = file_get_contents('php://input');
        $headers = getallheaders() ?? [];

        return new self($method, $path, [], $queryParams, $body, $headers);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getQuery(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getJsonBody(): array
    {
        if ($this->body === null) {
            return [];
        }
        return json_decode($this->body, associative: true) ?? [];
    }

    public function getHeader(string $key, ?string $default = null): ?string
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function hasHeader(string $key): bool
    {
        return isset($this->headers[strtolower($key)]);
    }

    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type', '');
        return strpos($contentType, 'application/json') !== false;
    }
}
