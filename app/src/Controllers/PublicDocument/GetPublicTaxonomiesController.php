<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\SectionRepository;
use App\Repositories\TagRepository;

class GetPublicTaxonomiesController extends AbstractController {

    public function process(Request $request): Response {
        // Endpoint public qui retourne sections + tags en une seule requête
        // Utile pour un front qui a besoin de construire la navigation et les filtres

        $sectionRepository = new SectionRepository();
        $tagRepository = new TagRepository();

        $allSections = $sectionRepository->findAllFlat();
        $tags = $tagRepository->findAllWithPublishedCount();

        // Construire l'arborescence des sections
        $sectionTree = $this->buildTree($allSections);

        return new Response(
            json_encode([
                'sections' => $sectionTree,
                'tags' => $tags,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    private function buildTree(array $sections, ?int $parentId = null): array {
        $tree = [];

        foreach ($sections as $section) {
            $sectionParentId = $section['parent_id'] !== null ? (int) $section['parent_id'] : null;

            if ($sectionParentId === $parentId) {
                $children = $this->buildTree($sections, (int) $section['id']);
                $node = [
                    'id' => (int) $section['id'],
                    'name' => $section['name'],
                    'slug' => $section['slug'],
                    'description' => $section['description'],
                ];

                if (!empty($children)) {
                    $node['children'] = $children;
                }

                $tree[] = $node;
            }
        }

        return $tree;
    }
}

?>