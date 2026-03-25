<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\DocumentRepository;
use App\Repositories\SectionRepository;

class GetPublicSectionDocumentsController extends AbstractController {
    public function process(Request $request): Response {
        $slug = trim((string) $request->getSlug('slug'));
        if ($slug === '') {
            return new Response(
                json_encode(['error' => 'invalid section slug']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $sectionRepository = new SectionRepository();
        $section = $sectionRepository->findBySlug($slug);
        if ($section === null) {
            return new Response(
                json_encode(['error' => 'section not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        $queryParams = $request->getUrlParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $tagSlug = isset($queryParams['tag']) ? trim((string) $queryParams['tag']) : null;
        $search = isset($queryParams['q']) ? trim((string) $queryParams['q']) : null;

        if ($tagSlug !== null && $tagSlug === '') {
            return new Response(
                json_encode(['error' => 'invalid tag filter']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $documentRepository = new DocumentRepository();
        $documents = $documentRepository->findPublicPaginated($limit, $offset, $section->getId(), $tagSlug, $search);
        $total = $documentRepository->countPublic($section->getId(), $tagSlug, $search);

        $documentIds = array_map(static fn(array $doc): int => (int) $doc['id'], $documents);
        $tagsByDocumentId = $documentRepository->findTagsForDocumentIds($documentIds);
        foreach ($documents as &$doc) {
            $doc['tags'] = $tagsByDocumentId[(int) $doc['id']] ?? [];
        }
        unset($doc);

        return new Response(
            json_encode([
                'section' => [
                    'id' => $section->getId(),
                    'name' => $section->getName(),
                    'slug' => $section->getSlug(),
                    'description' => $section->description,
                ],
                'data' => $documents,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
                'filters' => [
                    'section_slug' => $slug,
                    'tag' => $tagSlug,
                    'q' => $search,
                ],
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>