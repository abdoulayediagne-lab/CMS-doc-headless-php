<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\SectionRepository;

class GetPublicSectionsController extends AbstractController {

    public function process(Request $request): Response {
        // Endpoint public : pas besoin d'authentification
        // Retourne l'arborescence complète des sections (hiérarchie wiki)

        $sectionRepository = new SectionRepository();
        $allSections = $sectionRepository->findAllFlat();

        // Construire l'arborescence parent/enfant
        $tree = $this->buildTree($allSections);

        return new Response(
            json_encode([
                'data' => $tree,
                'total' => count($allSections),
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Construit un arbre hiérarchique à partir d'une liste plate de sections
     */
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
                    'sort_order' => (int) $section['sort_order'],
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