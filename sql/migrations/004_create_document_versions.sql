-- Migration 004 : Création de la table document_versions
-- Historique des versions (exigé par la consigne)

CREATE TABLE document_versions (
    id SERIAL PRIMARY KEY,
    document_id INT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    author_id INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_versions_document ON document_versions(document_id);
CREATE INDEX idx_versions_number ON document_versions(document_id, version_number);