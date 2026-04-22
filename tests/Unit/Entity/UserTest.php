<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('test@example.com', 'hashed_password', 'Test User');
    }

    public function testUserCanBeCreated(): void
    {
        $this->assertNotEmpty($this->user->getId());
        $this->assertSame('test@example.com', $this->user->getEmail());
        $this->assertSame('hashed_password', $this->user->getPassword());
        $this->assertSame('Test User', $this->user->getName());
    }

    public function testUserIsActiveByDefault(): void
    {
        $this->assertTrue($this->user->isActive());
    }

    public function testUserEmailCanBeUpdated(): void
    {
        $this->user->setEmail('new@example.com');
        $this->assertSame('new@example.com', $this->user->getEmail());
    }

    public function testUserPasswordCanBeUpdated(): void
    {
        $this->user->setPassword('new_hashed_password');
        $this->assertSame('new_hashed_password', $this->user->getPassword());
    }

    public function testUserCanBeDeactivated(): void
    {
        $this->user->setActive(false);
        $this->assertFalse($this->user->isActive());
    }

    public function testUserHasTimestamps(): void
    {
        $this->assertNotNull($this->user->getCreatedAt());
        $this->assertNotNull($this->user->getUpdatedAt());
    }

    public function testUserHasConversations(): void
    {
        $this->assertCount(0, $this->user->getConversations());
    }
}
