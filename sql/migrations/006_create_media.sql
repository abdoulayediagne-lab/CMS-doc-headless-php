-- Migration 006 : Création de la table media
-- Médiathèque (upload, alt text, liaison avec contenus)

CREATE TABLE media (
    id SERIAL PRIMARY KEY,
    document_id INT DEFAULT NULL REFERENCES documents(id) ON DELETE SET NULL,
    uploaded_by INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    filename VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_media_document ON media(document_id);
CREATE INDEX idx_media_uploaded_by ON media(uploaded_by);