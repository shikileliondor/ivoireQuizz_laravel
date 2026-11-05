<?php

namespace App\Support;

/**
 * Libellés français et couleurs de badge des valeurs techniques affichées dans l'admin.
 */
final class AdminLabels
{
    private const LABELS = [
        'difficulty' => ['easy' => ['Facile', 'green'], 'medium' => ['Moyen', 'amber'], 'hard' => ['Difficile', 'orange'], 'expert' => ['Expert', 'red']],
        'user_status' => ['active' => ['Actif', 'green'], 'suspended' => ['Suspendu', 'amber'], 'disabled' => ['Désactivé', 'slate']],
        'session_status' => ['started' => ['En cours', 'blue'], 'completed' => ['Réussie', 'green'], 'failed' => ['Échouée', 'red'], 'abandoned' => ['Abandonnée', 'slate']],
        'session_mode' => ['level' => ['Niveau', 'blue'], 'boss' => ['Boss', 'purple'], 'daily_challenge' => ['Défi du jour', 'amber'], 'chrono' => ['Chrono', 'slate'], 'mixed' => ['Mixte (ancien)', 'slate']],
        'report_status' => ['pending' => ['En attente', 'amber'], 'reviewed' => ['Examiné', 'blue'], 'fixed' => ['Corrigé', 'green'], 'rejected' => ['Rejeté', 'slate']],
        'report_reason' => ['wrong_answer' => ['Mauvaise réponse', 'red'], 'typo' => ['Faute de frappe', 'amber'], 'inappropriate' => ['Inapproprié', 'purple'], 'duplicate' => ['Doublon', 'blue'], 'other' => ['Autre', 'slate']],
        'question_type' => ['text' => ['Texte', 'slate'], 'image' => ['Image', 'blue'], 'audio' => ['Audio', 'purple']],
        'chest_type' => ['bronze' => ['Bronze', 'orange'], 'silver' => ['Argent', 'slate'], 'gold' => ['Or', 'amber']],
        'chest_status' => ['locked' => ['Verrouillé', 'slate'], 'available' => ['Disponible', 'blue'], 'opened' => ['Ouvert', 'green']],
        'rarity' => ['common' => ['Commun', 'slate'], 'rare' => ['Rare', 'blue'], 'epic' => ['Épique', 'purple'], 'legendary' => ['Légendaire', 'amber']],
        'collectible_type' => ['personality' => ['Personnalité', 'blue'], 'monument' => ['Monument', 'green']],
        'season_status' => ['scheduled' => ['Planifiée', 'blue'], 'active' => ['En cours', 'green'], 'completed' => ['Terminée', 'slate']],
        'push_status' => ['scheduled' => ['Programmée', 'purple'], 'queued' => ['En file', 'blue'], 'sending' => ['En cours', 'amber'], 'sent' => ['Envoyée', 'green'], 'failed' => ['Échec', 'red'], 'canceled' => ['Annulée', 'slate']],
        'reward_type' => ['xp' => ['XP', 'blue'], 'point' => ['Points', 'slate'], 'coin' => ['Pièces', 'amber'], 'gem' => ['Gemmes', 'purple'], 'life' => ['Vies', 'red']],
    ];

    public static function label(string $group, ?string $value): string
    {
        return self::LABELS[$group][$value][0] ?? (string) $value;
    }

    public static function color(string $group, ?string $value): string
    {
        return self::LABELS[$group][$value][1] ?? 'slate';
    }

    /**
     * @param  list<string>|null  $only  limite aux valeurs autorisées par la validation
     * @return array<string, string>
     */
    public static function options(string $group, ?array $only = null): array
    {
        $options = array_map(fn (array $entry): string => $entry[0], self::LABELS[$group] ?? []);

        return $only === null ? $options : array_intersect_key($options, array_flip($only));
    }
}
