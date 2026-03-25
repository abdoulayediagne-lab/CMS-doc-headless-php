<?php

namespace App\Lib\Security;

class CsrfGuard {
    private array $allowedOrigins;

    public function __construct(array $allowedOrigins) {
        $this->allowedOrigins = array_map([$this, 'normalizeOrigin'], $allowedOrigins);
    }

    public function validate(array $server): ?array {
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $origin = isset($server['HTTP_ORIGIN']) ? $this->normalizeOrigin((string) $server['HTTP_ORIGIN']) : null;
        if ($origin !== null) {
            if (in_array($origin, $this->allowedOrigins, true)) {
                return null;
            }

            return [
                'status' => 403,
                'error' => 'csrf check failed: origin not allowed',
            ];
        }

        $referer = (string) ($server['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $refererOrigin = $this->extractOriginFromUrl($referer);
            if ($refererOrigin !== null && in_array($refererOrigin, $this->allowedOrigins, true)) {
                return null;
            }

            return [
                'status' => 403,
                'error' => 'csrf check failed: referer not allowed',
            ];
        }

        return [
            'status' => 403,
            'error' => 'csrf check failed: missing origin/referer',
        ];
    }

    private function extractOriginFromUrl(string $url): ?string {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $this->normalizeOrigin($origin);
    }

    private function normalizeOrigin(string $origin): string {
        return rtrim(strtolower(trim($origin)), '/');
    }
}

?>