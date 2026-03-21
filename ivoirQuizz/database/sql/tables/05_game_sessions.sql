-- Table game_sessions
-- Enregistre les sessions de jeu, les scores et la catégorie jouée.
CREATE TABLE IF NOT EXISTS game_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    mode ENUM('category', 'mixed') NOT NULL,
    score INT NOT NULL DEFAULT 0,
    bonus_score INT NOT NULL DEFAULT 0,
    total_score INT NOT NULL DEFAULT 0,
    correct_answers TINYINT UNSIGNED NOT NULL DEFAULT 0,
    duration_seconds INT NOT NULL,
    completed_at TIMESTAMP NULL,
    INDEX idx_game_sessions_user_id (user_id),
    INDEX idx_game_sessions_category_id (category_id),
    INDEX idx_game_sessions_user_completed (user_id, completed_at),
    CONSTRAINT fk_game_sessions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_game_sessions_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
