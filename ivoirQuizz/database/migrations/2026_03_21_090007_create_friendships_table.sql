-- Table des relations d'amitié entre utilisateurs.
-- Gère les demandes et états d'amitié avec contrainte d'unicité par paire.
CREATE TABLE IF NOT EXISTS friendships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','accepted') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_friendships_requester_receiver (requester_id, receiver_id),
    KEY idx_friendships_requester_id (requester_id),
    KEY idx_friendships_receiver_id (receiver_id),
    KEY idx_friendships_status (status),
    CONSTRAINT fk_friendships_requester_id
        FOREIGN KEY (requester_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_friendships_receiver_id
        FOREIGN KEY (receiver_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
