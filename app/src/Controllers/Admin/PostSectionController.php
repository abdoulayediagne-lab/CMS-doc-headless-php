<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\SectionRepository;

class PostSectionController extends AbstractController {
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
        if ($name === '' || mb_strlen($name) > 100) {
            return new Response(
                json_encode(['error' => 'name must be between 1 and 100 chars']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $slug = isset($payload['slug']) ? $this->normalizeSlug((string) $payload['slug']) : $this->normalizeSlug($name);
        if ($slug === '' || mb_strlen($slug) > 120) {
            return new Response(
                json_encode(['error' => 'invalid slug']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $sectionRepository = new SectionRepository();
        if ($sectionRepository->findBySlug($slug) !== null) {
            return new Response(
                json_encode(['error' => 'slug already exists']),
                409,
                ['Content-Type' => 'application/json']
            );
        }

        $parentId = null;
        if (array_key_exists('parent_id', $payload) && $payload['parent_id'] !== null && $payload['parent_id'] !== '') {
            $parentIdCandidate = filter_var($payload['parent_id'], FILTER_VALIDATE_INT);
            if ($parentIdCandidate === false || $parentIdCandidate <= 0) {
                return new Response(
                    json_encode(['error' => 'parent_id must be a positive integer']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $parentId = (int) $parentIdCandidate;
        }

        if ($parentId !== null && $parentId > 0 && $sectionRepository->findById($parentId) === null) {
            return new Response(
                json_encode(['error' => 'parent section not found']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $description = $payload['description'] ?? null;
        if ($description !== null) {
            if (!is_string($description)) {
                return new Response(
                    json_encode(['error' => 'description must be a string or null']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $description = trim($description);
            if ($description === '') {
                $description = null;
            }
        }

        $sortOrder = 0;
        if (array_key_exists('sort_order', $payload)) {
            $sortOrderCandidate = filter_var($payload['sort_order'], FILTER_VALIDATE_INT);
            if ($sortOrderCandidate === false) {
                return new Response(
                    json_encode(['error' => 'sort_order must be an integer']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $sortOrder = (int) $sortOrderCandidate;
        }

        $section = $sectionRepository->create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'sort_order' => $sortOrder,
        ]);

        if ($section === null) {
            return new Response(
                json_encode(['error' => 'unable to create section']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $section->getId(),
                'name' => $section->getName(),
                'slug' => $section->getSlug(),
                'parent_id' => $section->getParentId(),
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