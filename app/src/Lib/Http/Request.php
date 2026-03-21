<?php

namespace App\Lib\Http;

class Request {
    private string $uri;
    private string $path;
    private string $method;
    private array $headers;
    private array $slugs;
    private array $urlParams;
    private string $payload;

    public function __construct() {
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->path = parse_url($this->uri, PHP_URL_PATH);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        $this->urlParams = $_GET;
        $this->payload = file_get_contents('php://input');
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function addSlug(string $key, string $value): self {
        $this->slugs[$key] = $value;
        
        return $this;
    }

    public function getSlugs(): array {
        return $this->slugs;
    }

    public function getSlug(string $key): string {
        if(!isset($this->slugs[$key])) {
            return '';
        }
        
        return $this->slugs[$key];
    }

    public function getUrlParams(): array {
        return $this->urlParams;
    }
    
    public function getHeaders(): array {
        return $this->headers;
    }

    public function getHeader(string $name): ?string {
        foreach ($this->headers as $headerName => $value) {
            if (strtolower($headerName) === strtolower($name)) {
                return $value;
            }
        }

        return null;
    }

    public function getBearerToken(): ?string {
        $authorizationHeader = $this->getHeader('Authorization');
        if ($authorizationHeader === null) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.*)$/i', $authorizationHeader, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    public function getPayload(): string {
        return $this->payload;
    }
}
