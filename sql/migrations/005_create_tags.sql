-- Migration 005 : Création des tables tags et document_tags
-- Taxonomies pour étiqueter les documents

CREATE TABLE tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(60) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE document_tags (
    document_id INT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    tag_id INT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (document_id, tag_id)
);

CREATE INDEX idx_tags_slug ON tags(slug);
CREATE INDEX idx_document_tags_tag ON document_tags(tag_id);