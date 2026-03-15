-- Migration 001 : Création de la table users
-- Gestion des utilisateurs avec rôles (admin, editor, author, reader)
 
CREATE TYPE user_role AS ENUM ('admin', 'editor', 'author', 'reader');
 
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'reader',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
 
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);