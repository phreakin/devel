<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testApplicationCanBeInstantiated(): void
    {
        $app = new Application('test', true);
        $this->assertSame('test', $app->getEnvironment());
    }

    public function testApplicationDebugMode(): void
    {
        $app = new Application('development', true);
        $this->assertTrue($app->isDebug());
    }

    public function testApplicationProductionMode(): void
    {
        $app = new Application('production', false);
        $this->assertFalse($app->isDebug());
    }
}
