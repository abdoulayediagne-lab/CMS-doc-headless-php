<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\DocumentRepository;

class GetPublicDocumentsController extends AbstractController {
    public function process(Request $request): Response {
        $queryParams = $request->getUrlParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $sectionId = isset($queryParams['section_id']) ? (int) $queryParams['section_id'] : null;
        $tagSlug = isset($queryParams['tag']) ? trim((string) $queryParams['tag']) : null;
        $search = isset($queryParams['q']) ? trim((string) $queryParams['q']) : null;

        if ($sectionId !== null && $sectionId <= 0) {
            return new Response(
                json_encode(['error' => 'invalid section_id filter']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if ($tagSlug !== null && $tagSlug === '') {
            return new Response(
                json_encode(['error' => 'invalid tag filter']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $documentRepository = new DocumentRepository();
        $documents = $documentRepository->findPublicPaginated($limit, $offset, $sectionId, $tagSlug, $search);
        $total = $documentRepository->countPublic($sectionId, $tagSlug, $search);

        $documentIds = array_map(static fn(array $doc): int => (int) $doc['id'], $documents);
        $tagsByDocumentId = $documentRepository->findTagsForDocumentIds($documentIds);
        foreach ($documents as &$doc) {
            $doc['tags'] = $tagsByDocumentId[(int) $doc['id']] ?? [];
        }
        unset($doc);

        return new Response(
            json_encode([
                'data' => $documents,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
                'filters' => [
                    'section_id' => $sectionId,
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