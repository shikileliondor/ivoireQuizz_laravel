-- Table friendships
-- Gère les demandes d'amitié entre utilisateurs.
CREATE TABLE IF NOT EXISTS friendships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_friendships_requester_receiver (requester_id, receiver_id),
    INDEX idx_friendships_requester_id (requester_id),
    INDEX idx_friendships_receiver_id (receiver_id),
    INDEX idx_friendships_receiver_status (receiver_id, status),
    CONSTRAINT fk_friendships_requester
        FOREIGN KEY (requester_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_friendships_receiver
        FOREIGN KEY (receiver_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
