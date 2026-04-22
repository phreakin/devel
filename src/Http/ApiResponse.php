<?php

declare(strict_types=1);

namespace App\Http;

class ApiResponse
{
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_CONFLICT = 409;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    private int $statusCode;
    private array $data;
    private ?string $message;
    private array $errors = [];

    private function __construct(int $statusCode, array $data = [], ?string $message = null)
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->message = $message;
    }

    public static function success(array $data = [], int $statusCode = self::HTTP_OK, ?string $message = null): self
    {
        return new self($statusCode, $data, $message ?? 'Success');
    }

    public static function created(array $data = [], ?string $message = null): self
    {
        return new self(self::HTTP_CREATED, $data, $message ?? 'Created');
    }

    public static function noContent(): self
    {
        return new self(self::HTTP_NO_CONTENT);
    }

    public static function badRequest(?string $message = null, array $errors = []): self
    {
        $response = new self(self::HTTP_BAD_REQUEST, [], $message ?? 'Bad Request');
        $response->errors = $errors;
        return $response;
    }

    public static function unauthorized(?string $message = null): self
    {
        return new self(self::HTTP_UNAUTHORIZED, [], $message ?? 'Unauthorized');
    }

    public static function forbidden(?string $message = null): self
    {
        return new self(self::HTTP_FORBIDDEN, [], $message ?? 'Forbidden');
    }

    public static function notFound(?string $message = null): self
    {
        return new self(self::HTTP_NOT_FOUND, [], $message ?? 'Not Found');
    }

    public static function conflict(?string $message = null): self
    {
        return new self(self::HTTP_CONFLICT, [], $message ?? 'Conflict');
    }

    public static function internalError(?string $message = null): self
    {
        return new self(self::HTTP_INTERNAL_SERVER_ERROR, [], $message ?? 'Internal Server Error');
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function toJson(): string
    {
        $payload = [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
            'message' => $this->message,
        ];

        if (!empty($this->data)) {
            $payload['data'] = $this->data;
        }

        if (!empty($this->errors)) {
            $payload['errors'] = $this->errors;
        }

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function getBody(): string
    {
        return $this->toJson();
    }
}
