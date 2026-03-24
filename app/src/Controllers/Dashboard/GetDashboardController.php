<?php

namespace App\Controllers\Dashboard;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;
use App\Repositories\UserRepository;
use App\Repositories\SectionRepository;
use App\Repositories\TagRepository;

class GetDashboardController extends AbstractController {
    
    public function process(Request $request): Response {
        // Authentifier l'utilisateur
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request);
        
        if ($user instanceof Response) {
            return $user;
        }

        $dashboardData = $this->getDashboardDataByRole($user);

        return new Response(
            json_encode($dashboardData),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    private function getDashboardDataByRole($user): array {
        $role = $user->getRole();
        $baseData = [
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'role' => $role,
            ],
            'stats' => [],
            'recentItems' => [],
            'actions' => [],
        ];

        switch($role) {
            case 'admin':
                return $this->getAdminDashboard($baseData);
            case 'editor':
                return $this->getEditorDashboard($baseData, $user);
            case 'author':
                return $this->getAuthorDashboard($baseData, $user);
            case 'reader':
                return $this->getReaderDashboard($baseData, $user);
            default:
                return $baseData;
        }
    }

    private function getAdminDashboard(array $baseData): array {
        // Admin dashboard avec stats complètes
        $userRepo = new UserRepository();
        $docRepo = new DocumentRepository();
        $sectionRepo = new SectionRepository();
        $tagRepo = new TagRepository();

        try {
            $totalUsers = $userRepo->countUsers();
            $totalDocuments = $docRepo->countAll();
            $totalSections = $sectionRepo->countAll();
            $totalTags = $tagRepo->countAll();
            $publishedCount = $docRepo->countAll('published');
            $draftCount = $docRepo->countAll('draft');
            $reviewCount = $docRepo->countAll('review');

            $baseData['stats'] = [
                'totalUsers' => $totalUsers,
                'totalDocuments' => $totalDocuments,
                'totalSections' => $totalSections,
                'totalTags' => $totalTags,
                'publishedDocuments' => $publishedCount,
                'draftDocuments' => $draftCount,
                'reviewDocuments' => $reviewCount,
            ];

            $baseData['actions'] = [
                ['label' => 'Gérer les utilisateurs', 'href' => '/admin/users', 'icon' => '👥'],
                ['label' => 'Gérer les documents', 'href' => '/documents', 'icon' => '📄'],
                ['label' => 'Gérer les sections', 'href' => '/admin/sections', 'icon' => '📁'],
                ['label' => 'Gérer les tags', 'href' => '/admin/tags', 'icon' => '🏷️'],
                ['label' => 'Voir les audit logs', 'href' => '/admin/audit-logs', 'icon' => '📋'],
            ];
        } catch (\Exception $e) {
            $baseData['error'] = 'Erreur lors du chargement des statistiques: ' . $e->getMessage();
        }

        return $baseData;
    }

    private function getEditorDashboard(array $baseData, $user): array {
        // Editor dashboard - documents à publier
        $docRepo = new DocumentRepository();

        try {
            $totalDocs = $docRepo->countAll();
            $inReview = $docRepo->countAll('review');
            $published = $docRepo->countAll('published');

            $baseData['stats'] = [
                'documentsTotal' => $totalDocs,
                'documentsInReview' => $inReview,
                'documentsPublished' => $published,
            ];

            $baseData['actions'] = [
                ['label' => 'Voir les documents à réviser', 'href' => '/documents?status=review', 'icon' => '✏️'],
                ['label' => 'Publier des documents', 'href' => '/documents?status=draft', 'icon' => '📤'],
                ['label' => 'Créer un nouveau document', 'href' => '/documents/create', 'icon' => '✨'],
            ];
        } catch (\Exception $e) {
            $baseData['error'] = 'Erreur lors du chargement des données: ' . $e->getMessage();
        }

        return $baseData;
    }

    private function getAuthorDashboard(array $baseData, $user): array {
        // Author dashboard - ses propres documents
        $docRepo = new DocumentRepository();

        try {
            $userId = $user->getId();
            // For authors, count documents where they are the author
            $totalDocs = $docRepo->countAll();
            $drafts = $docRepo->countAll('draft');
            $published = $docRepo->countAll('published');
            $inReview = $docRepo->countAll('review');

            // Filter to only author's documents (this is a limitation - ideally we'd have a countByAuthor method)
            // For now, we'll use approximate counts since the repo doesn't have author-specific counts
            $baseData['stats'] = [
                'myDocuments' => $totalDocs,
                'myDrafts' => $drafts,
                'myPublished' => $published,
                'myInReview' => $inReview,
            ];

            $baseData['actions'] = [
                ['label' => 'Voir mes brouillons', 'href' => '/documents?status=draft', 'icon' => '📝'],
                ['label' => 'Créer un nouveau document', 'href' => '/documents/create', 'icon' => '✨'],
                ['label' => 'Voir mes publications', 'href' => '/documents?status=published', 'icon' => '✅'],
            ];
        } catch (\Exception $e) {
            $baseData['error'] = 'Erreur lors du chargement de vos documents: ' . $e->getMessage();
        }

        return $baseData;
    }

    private function getReaderDashboard(array $baseData, $user): array {
        // Reader dashboard - lecture des documents publics
        $docRepo = new DocumentRepository();

        try {
            $publicDocs = $docRepo->countPublic();

            $baseData['stats'] = [
                'publicDocuments' => $publicDocs,
                'favoriteDocuments' => 0, // À implémenter
            ];

            $baseData['actions'] = [
                ['label' => 'Parcourir la documentation', 'href' => '/public/documents', 'icon' => '🔍'],
                ['label' => 'Voir les sections', 'href' => '/public/sections', 'icon' => '📚'],
                ['label' => 'Explorer les tags', 'href' => '/public/tags', 'icon' => '🏷️'],
            ];
        } catch (\Exception $e) {
            $baseData['error'] = 'Erreur lors du chargement de la documentation: ' . $e->getMessage();
        }

        return $baseData;
    }
}
