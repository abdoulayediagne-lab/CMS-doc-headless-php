-- Seeder : Données de test pour le CMS Wiki
-- Mot de passe pour tous les users : "password123"
-- Le hash correspond à password_hash('password123', PASSWORD_BCRYPT)

-- ============================================
-- USERS
-- ============================================
INSERT INTO users (username, email, password_hash, role) VALUES
    ('admin', 'admin@cms-wiki.fr', '$2y$12$sP1u4PzEnysEUJ07oCu2hOqJ0RviT3FQeG7Y6ZPnxhX41w/zLmpxi', 'admin'),
    ('editeur1', 'editeur@cms-wiki.fr', '$2y$12$sP1u4PzEnysEUJ07oCu2hOqJ0RviT3FQeG7Y6ZPnxhX41w/zLmpxi', 'editor'),
    ('auteur1', 'auteur@cms-wiki.fr', '$2y$12$sP1u4PzEnysEUJ07oCu2hOqJ0RviT3FQeG7Y6ZPnxhX41w/zLmpxi', 'author'),
    ('lecteur1', 'lecteur@cms-wiki.fr', '$2y$12$sP1u4PzEnysEUJ07oCu2hOqJ0RviT3FQeG7Y6ZPnxhX41w/zLmpxi', 'reader');

-- ============================================
-- SECTIONS (hiérarchie wiki)
-- ============================================
INSERT INTO sections (parent_id, name, slug, description, sort_order) VALUES
    (NULL, 'Guide de démarrage', 'guide-demarrage', 'Tout pour bien commencer avec le CMS', 1),
    (NULL, 'Architecture technique', 'architecture-technique', 'Documentation technique du projet', 2),
    (NULL, 'API Reference', 'api-reference', 'Documentation des endpoints API', 3),
    (1, 'Installation', 'installation', 'Guide d installation pas à pas', 1),
    (1, 'Configuration', 'configuration', 'Paramétrage du CMS', 2),
    (2, 'Base de données', 'base-de-donnees', 'Schéma et modélisation', 1);

-- ============================================
-- DOCUMENTS
-- ============================================
INSERT INTO documents (section_id, author_id, title, slug, content, status, meta_title, meta_description, sort_order, published_at) VALUES
    (4, 1, 'Installation avec Docker', 'installation-docker', 'Ce guide vous montre comment installer le CMS avec Docker et docker-compose. Prérequis : Docker Desktop installé sur votre machine.', 'published', 'Installation Docker - CMS Wiki', 'Guide complet pour installer le CMS Wiki avec Docker', 1, NOW()),
    (5, 1, 'Configuration de la base de données', 'configuration-bdd', 'Le CMS utilise PostgreSQL 18. Voici comment configurer votre connexion à la base de données.', 'published', 'Configuration BDD - CMS Wiki', 'Configurer PostgreSQL 18 pour le CMS', 2, NOW()),
    (6, 3, 'Schéma de la base de données', 'schema-bdd', 'Voici le schéma complet de la base de données du CMS Wiki avec toutes les tables et relations.', 'draft', NULL, NULL, 1, NULL),
    (3, 2, 'Endpoints de l API publique', 'endpoints-api', 'L API publique expose des endpoints read-only pour consulter les documents, sections et tags.', 'review', 'API Endpoints - CMS Wiki', 'Liste des endpoints de l API REST', 1, NULL);

-- ============================================
-- DOCUMENT VERSIONS
-- ============================================
INSERT INTO document_versions (document_id, author_id, title, content, version_number) VALUES
    (1, 1, 'Installation avec Docker', 'Premier brouillon du guide d installation.', 1),
    (1, 1, 'Installation avec Docker', 'Ce guide vous montre comment installer le CMS avec Docker et docker-compose. Prérequis : Docker Desktop installé sur votre machine.', 2);

-- ============================================
-- TAGS
-- ============================================
INSERT INTO tags (name, slug) VALUES
    ('Docker', 'docker'),
    ('PostgreSQL', 'postgresql'),
    ('API', 'api'),
    ('Installation', 'installation'),
    ('Configuration', 'configuration');

-- ============================================
-- DOCUMENT_TAGS
-- ============================================
INSERT INTO document_tags (document_id, tag_id) VALUES
    (1, 1),
    (1, 4),
    (2, 2),
    (2, 5),
    (4, 3);

-- ============================================
-- PAGE VIEWS (analytics)
-- ============================================
INSERT INTO page_views (document_id, ip_address, user_agent, viewed_at) VALUES
    (1, '192.168.1.10', 'Mozilla/5.0', NOW() - INTERVAL '2 days'),
    (1, '192.168.1.11', 'Mozilla/5.0', NOW() - INTERVAL '1 day'),
    (1, '192.168.1.12', 'Mozilla/5.0', NOW()),
    (2, '192.168.1.10', 'Mozilla/5.0', NOW() - INTERVAL '1 day'),
    (2, '192.168.1.13', 'Mozilla/5.0', NOW());

-- ============================================
-- AUDIT LOG
-- ============================================
INSERT INTO audit_log (user_id, action, entity_type, entity_id, new_values) VALUES
    (1, 'create', 'document', 1, '{"title": "Installation avec Docker"}'),
    (1, 'publish', 'document', 1, '{"status": "published"}'),
    (3, 'create', 'document', 3, '{"title": "Schéma de la base de données"}');