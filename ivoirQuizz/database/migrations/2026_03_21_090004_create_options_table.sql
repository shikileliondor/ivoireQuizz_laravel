-- Table des options de réponse.
-- Chaque option appartient à une question et indique si elle est correcte.
CREATE TABLE IF NOT EXISTS options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    KEY idx_options_question_id (question_id),
    KEY idx_options_question_correct (question_id, is_correct),
    CONSTRAINT fk_options_question_id
        FOREIGN KEY (question_id) REFERENCES questions(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
