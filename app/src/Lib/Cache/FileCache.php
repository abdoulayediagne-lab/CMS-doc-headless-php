<?php

namespace App\Lib\Cache;

class FileCache {
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(?string $cacheDir = null, int $defaultTtl = 60) {
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 3) . '/cache/http';
        $this->defaultTtl = max(1, $defaultTtl);
    }

    public function buildKey(string $prefix, array $data): string {
        ksort($data);
        $rawKey = json_encode($data);

        return $prefix . '_' . sha1((string) $rawKey);
    }

    public function get(string $key): ?string {
        $path = $this->getFilePath($key);
        if (!is_file($path)) {
            return null;
        }

        $cached = @file_get_contents($path);
        if ($cached === false) {
            return null;
        }

        $payload = json_decode($cached, true);
        if (!is_array($payload) || !isset($payload['expires_at']) || !array_key_exists('content', $payload)) {
            @unlink($path);
            return null;
        }

        if ((int) $payload['expires_at'] < time()) {
            @unlink($path);
            return null;
        }

        return (string) $payload['content'];
    }

    public function set(string $key, string $content, ?int $ttl = null): void {
        $this->ensureCacheDir();

        $effectiveTtl = $ttl !== null ? max(1, $ttl) : $this->defaultTtl;
        $payload = json_encode([
            'expires_at' => time() + $effectiveTtl,
            'content' => $content,
        ]);

        if ($payload === false) {
            return;
        }

        @file_put_contents($this->getFilePath($key), $payload, LOCK_EX);
    }

    public function deleteByPrefix(string $prefix): int {
        $this->ensureCacheDir();

        $deleted = 0;
        $pattern = rtrim($this->cacheDir, '/\\') . '/' . $prefix . '_*.cache';
        $files = glob($pattern);
        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function ensureCacheDir(): void {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    private function getFilePath(string $key): string {
        return rtrim($this->cacheDir, '/\\') . '/' . $key . '.cache';
    }
}

?>