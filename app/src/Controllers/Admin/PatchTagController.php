<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\TagRepository;

class PatchTagController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid tag id']),
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

        $tagRepository = new TagRepository();
        $existingTag = $tagRepository->findById($id);
        if ($existingTag === null) {
            return new Response(
                json_encode(['error' => 'tag not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        $updateData = [];

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '' || mb_strlen($name) > 50) {
                return new Response(
                    json_encode(['error' => 'name must be between 1 and 50 chars']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $updateData['name'] = $name;
        }

        if (array_key_exists('slug', $payload)) {
            $slug = $this->normalizeSlug((string) $payload['slug']);
            if ($slug === '' || mb_strlen($slug) > 60) {
                return new Response(
                    json_encode(['error' => 'invalid slug']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $foundTag = $tagRepository->findBySlug($slug);
            if ($foundTag !== null && $foundTag->getId() !== $id) {
                return new Response(
                    json_encode(['error' => 'slug already exists']),
                    409,
                    ['Content-Type' => 'application/json']
                );
            }

            $updateData['slug'] = $slug;
        }

        if (empty($updateData)) {
            return new Response(
                json_encode(['error' => 'nothing to update']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $updatedTag = $tagRepository->updateTag($id, $updateData);
        if ($updatedTag === null) {
            return new Response(
                json_encode(['error' => 'unable to update tag']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $updatedTag->getId(),
                'name' => $updatedTag->getName(),
                'slug' => $updatedTag->getSlug(),
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