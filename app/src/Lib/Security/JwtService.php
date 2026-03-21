<?php

namespace App\Lib\Security;

class JwtService {
    private string $secret;
    private int $ttl;

    public function __construct(?string $secret = null, int $ttl = 3600) {
        $this->secret = $secret ?? ($_ENV['JWT_SECRET'] ?? 'change-me-in-production');
        $this->ttl = $ttl;
    }

    public function generateToken(array $payload): string {
        $now = time();

        $payload['iat'] = $now;
        $payload['exp'] = $now + $this->ttl;

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);

        return $encodedHeader . '.' . $encodedPayload . '.' . $this->base64UrlEncode($signature);
    }

    public function validateToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);
        $decodedSignature = $this->base64UrlDecode($encodedSignature);

        if (!hash_equals($expectedSignature, $decodedSignature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (!is_array($payload)) {
            return null;
        }

        if (!isset($payload['exp']) || time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}

?>