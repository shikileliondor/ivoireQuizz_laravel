-- Table des réponses par session.
-- Trace la réponse donnée, le temps de réponse et les points gagnés par question.
CREATE TABLE IF NOT EXISTS session_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    selected_option_id BIGINT UNSIGNED NULL,
    is_correct BOOLEAN NOT NULL,
    response_time_seconds TINYINT UNSIGNED NOT NULL,
    points_earned INT NOT NULL DEFAULT 0,
    KEY idx_session_answers_session_id (session_id),
    KEY idx_session_answers_question_id (question_id),
    KEY idx_session_answers_selected_option_id (selected_option_id),
    KEY idx_session_answers_session_question (session_id, question_id),
    CONSTRAINT fk_session_answers_session_id
        FOREIGN KEY (session_id) REFERENCES game_sessions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_session_answers_question_id
        FOREIGN KEY (question_id) REFERENCES questions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_session_answers_selected_option_id
        FOREIGN KEY (selected_option_id) REFERENCES options(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
