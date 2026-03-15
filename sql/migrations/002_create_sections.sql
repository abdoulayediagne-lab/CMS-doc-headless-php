-- Migration 002 : Création de la table sections
-- Sections hiérarchiques pour organiser la documentation (wiki)
 
CREATE TABLE sections (
    id SERIAL PRIMARY KEY,
    parent_id INT DEFAULT NULL REFERENCES sections(id) ON DELETE SET NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
 
CREATE INDEX idx_sections_parent ON sections(parent_id);
CREATE INDEX idx_sections_slug ON sections(slug);
CREATE INDEX idx_sections_sort ON sections(sort_order);