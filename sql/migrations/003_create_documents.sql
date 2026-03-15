-- Migration 003 : Création de la table documents
-- Articles/pages du wiki avec workflow de publication

CREATE TYPE document_status AS ENUM ('draft', 'review', 'published', 'archived');

CREATE TABLE documents (
    id SERIAL PRIMARY KEY,
    section_id INT DEFAULT NULL REFERENCES sections(id) ON DELETE SET NULL,
    author_id INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) NOT NULL UNIQUE,
    content TEXT DEFAULT '',
    status document_status NOT NULL DEFAULT 'draft',
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(500) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    published_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_documents_slug ON documents(slug);
CREATE INDEX idx_documents_status ON documents(status);
CREATE INDEX idx_documents_section ON documents(section_id);
CREATE INDEX idx_documents_author ON documents(author_id);
CREATE INDEX idx_documents_published ON documents(published_at);

-- Index full-text pour la recherche avancée (bonus)
CREATE INDEX idx_documents_search ON documents
    USING GIN (to_tsvector('french', title || ' ' || content));