<?php

declare(strict_types=1);

namespace App;

class Application
{
    private string $environment;
    private bool $debug;

    public function __construct(string $environment = 'production', bool $debug = false)
    {
        $this->environment = $environment;
        $this->debug = $debug;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function run(): void
    {
        echo "Application running in {$this->environment} mode\n";
    }
}
