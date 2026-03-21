<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\TagRepository;

class PostTagController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $payload = json_decode($request->getPayload(), true);
        if (!is_array($payload) || empty($payload['name'])) {
            return new Response(
                json_encode(['error' => 'name is required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $name = trim((string) $payload['name']);
        $slug = isset($payload['slug']) ? $this->normalizeSlug((string) $payload['slug']) : $this->normalizeSlug($name);
        if ($slug === '') {
            return new Response(
                json_encode(['error' => 'invalid slug']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $tagRepository = new TagRepository();
        if ($tagRepository->findBySlug($slug) !== null) {
            return new Response(
                json_encode(['error' => 'slug already exists']),
                409,
                ['Content-Type' => 'application/json']
            );
        }

        $tag = $tagRepository->create($name, $slug);
        if ($tag === null) {
            return new Response(
                json_encode(['error' => 'unable to create tag']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'slug' => $tag->getSlug(),
            ]),
            201,
            ['Content-Type' => 'application/json']
        );
    }

    private function normalizeSlug(string $value): string {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        return trim($slug, '-');
    }
}

?>