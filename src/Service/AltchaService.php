<?php

declare(strict_types=1);

namespace EICC\SendPoint\Service;

class AltchaService
{
    public function __construct(
        private string $hmacKey,
        private int $maxNumber = 100000 // Adjust difficulty
    ) {}

    public function generateChallenge(): array
    {
        // Embed timestamp in salt for expiration
        $random = bin2hex(random_bytes(12));
        $timestamp = time();
        $salt = $random . '.' . $timestamp;

        $number = random_int(0, $this->maxNumber);
        $challenge = hash('sha256', $salt . $number);

        $signature = hash_hmac('sha256', $challenge . $salt, $this->hmacKey);

        return [
            'algorithm' => 'SHA-256',
            'challenge' => $challenge,
            'salt' => $salt,
            'signature' => $signature,
        ];
    }

    public function verifySolution(string $payloadBase64): bool
    {
        $json = base64_decode($payloadBase64);
        if (!$json) {
            return false;
        }

        $data = json_decode($json, true);
        if (!isset($data['algorithm'], $data['challenge'], $data['salt'], $data['signature'], $data['number'])) {
            return false;
        }

        if ($data['algorithm'] !== 'SHA-256') {
            return false;
        }

        // 1. Verify Signature (Integrity)
        $expectedSignature = hash_hmac('sha256', $data['challenge'] . $data['salt'], $this->hmacKey);
        if (!hash_equals($expectedSignature, $data['signature'])) {
            return false;
        }

        // 2. Verify Expiration (5 minutes)
        $parts = explode('.', $data['salt']);
        if (count($parts) !== 2) {
            return false;
        }
        $timestamp = (int) $parts[1];
        if (time() - $timestamp > 300) { // 5 minutes
            return false;
        }

        // 3. Verify Proof of Work
        $computedChallenge = hash('sha256', $data['salt'] . $data['number']);
        if (!hash_equals($data['challenge'], $computedChallenge)) {
            return false;
        }

        return true;
    }
}
