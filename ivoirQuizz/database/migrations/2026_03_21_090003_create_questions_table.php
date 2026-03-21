-- Table des questions de quiz.
-- Référence une catégorie et stocke le type, la difficulté et l'explication.
CREATE TABLE IF NOT EXISTS questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    type ENUM('qcm','vrai_faux') NOT NULL,
    question_text TEXT NOT NULL,
    explanation TEXT NULL,
    difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_questions_category_id (category_id),
    KEY idx_questions_is_active (is_active),
    KEY idx_questions_category_active (category_id, is_active),
    CONSTRAINT fk_questions_category_id
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
