<?php

declare(strict_types=1);

namespace EICC\SendPoint\Service;

use EICC\SendPoint\Exception\RateLimitException;

class RateLimitService
{
    public function __construct(
        private string $storageDir,
        private int $limitSeconds
    ) {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function checkLimit(string $ip): void
    {
        // Sanitize IP to be safe for filename
        $filename = $this->storageDir . '/' . md5($ip) . '.txt';

        if (file_exists($filename)) {
            $lastRequestTime = (int) file_get_contents($filename);
            $currentTime = time();

            if (($currentTime - $lastRequestTime) < $this->limitSeconds) {
                throw new RateLimitException("Rate limit exceeded. Please try again later.");
            }
        }

        // Update timestamp
        file_put_contents($filename, (string) time());

        // Garbage Collection (1% chance)
        if (rand(1, 100) === 1) {
            $this->cleanup();
        }
    }

    private function cleanup(): void
    {
        $files = glob($this->storageDir . '/*.txt');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                // If the file is older than the limit window, it's safe to delete
                // because the user is no longer rate-limited.
                if (($now - filemtime($file)) > $this->limitSeconds) {
                    unlink($file);
                }
            }
        }
    }
}
