<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\SectionRepository;

class PatchSectionController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid section id']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $payload = json_decode($request->getPayload(), true);
        if (!is_array($payload)) {
            return new Response(
                json_encode(['error' => 'invalid payload']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $sectionRepository = new SectionRepository();
        $existingSection = $sectionRepository->findById($id);
        if ($existingSection === null) {
            return new Response(
                json_encode(['error' => 'section not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        $updateData = [];

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                return new Response(
                    json_encode(['error' => 'name cannot be empty']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $updateData['name'] = $name;
        }

        if (array_key_exists('slug', $payload)) {
            $slug = $this->normalizeSlug((string) $payload['slug']);
            if ($slug === '') {
                return new Response(
                    json_encode(['error' => 'invalid slug']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $foundSection = $sectionRepository->findBySlug($slug);
            if ($foundSection !== null && $foundSection->getId() !== $id) {
                return new Response(
                    json_encode(['error' => 'slug already exists']),
                    409,
                    ['Content-Type' => 'application/json']
                );
            }

            $updateData['slug'] = $slug;
        }

        if (array_key_exists('parent_id', $payload)) {
            $parentId = $payload['parent_id'] !== null ? (int) $payload['parent_id'] : null;
            if ($parentId !== null) {
                if ($parentId <= 0 || $parentId === $id || $sectionRepository->findById($parentId) === null) {
                    return new Response(
                        json_encode(['error' => 'invalid parent_id']),
                        400,
                        ['Content-Type' => 'application/json']
                    );
                }
            }
            $updateData['parent_id'] = $parentId;
        }

        if (array_key_exists('description', $payload)) {
            $updateData['description'] = $payload['description'];
        }

        if (array_key_exists('sort_order', $payload)) {
            $updateData['sort_order'] = (int) $payload['sort_order'];
        }

        $updatedSection = $sectionRepository->updateSection($id, $updateData);
        if ($updatedSection === null) {
            return new Response(
                json_encode(['error' => 'unable to update section']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $updatedSection->getId(),
                'name' => $updatedSection->getName(),
                'slug' => $updatedSection->getSlug(),
                'parent_id' => $updatedSection->getParentId(),
            ]),
            200,
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