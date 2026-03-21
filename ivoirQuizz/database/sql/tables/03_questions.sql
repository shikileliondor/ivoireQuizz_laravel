-- Table questions
-- Contient les questions d'une catégorie, leur type et leur difficulté.
CREATE TABLE IF NOT EXISTS questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    type ENUM('qcm', 'vrai_faux') NOT NULL,
    question_text TEXT NOT NULL,
    explanation TEXT NULL,
    difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_questions_category_id (category_id),
    INDEX idx_questions_active_difficulty (is_active, difficulty),
    CONSTRAINT fk_questions_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
