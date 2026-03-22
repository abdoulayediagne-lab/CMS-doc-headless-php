<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\DocumentRepository;
use App\Repositories\SectionRepository;

class GetSitemapController extends AbstractController {

    public function process(Request $request): Response {
        $documentRepository = new DocumentRepository();
        $sectionRepository = new SectionRepository();

        // Récupérer tous les documents publiés
        $documents = $documentRepository->findPublicPaginated(1000, 0);
        $sections = $sectionRepository->findAllFlat();

        $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8080');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Page d'accueil
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . $baseUrl . '/</loc>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";

        // Sections
        foreach ($sections as $section) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $baseUrl . '/public/sections/' . htmlspecialchars($section['slug']) . '</loc>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        // Documents publiés
        foreach ($documents as $doc) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $baseUrl . '/public/documents/' . htmlspecialchars($doc['slug']) . '</loc>' . "\n";
            if (!empty($doc['updated_at'])) {
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($doc['updated_at'])) . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>monthly</changefreq>' . "\n";
            $xml .= '    <priority>0.6</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return new Response(
            $xml,
            200,
            ['Content-Type' => 'application/xml']
        );
    }
}

?>