<?php

namespace App\Support;

use Normalizer;

/**
 * Pseudo public affiché aux autres joueurs (accueil, classements, amis, défis).
 *
 * Il n'est pas unique : le code ami identifie déjà les joueurs.
 */
final class Pseudo
{
    public const PATTERN = '/^[\p{L}\p{N}_-]{3,15}$/u';

    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 15;

    public const MESSAGE = 'Le pseudo doit contenir 3 à 15 caractères (lettres, chiffres, _ ou -).';

    /** @return list<string> Règles de validation, sans « required »/« nullable ». */
    public static function rules(): array
    {
        return ['string', 'regex:'.self::PATTERN];
    }

    /** @return array<string, string> Le même message pour toute erreur sur le champ. */
    public static function messages(string $field = 'pseudo'): array
    {
        return [
            "{$field}.required" => self::MESSAGE,
            "{$field}.required_without" => self::MESSAGE,
            "{$field}.string" => self::MESSAGE,
            "{$field}.regex" => self::MESSAGE,
        ];
    }

    /** Retire les caractères interdits et coupe à 15 ; null si le résultat est trop court. */
    public static function clean(?string $value): ?string
    {
        $value = self::normalize((string) $value);
        $value = (string) preg_replace('/[^\p{L}\p{N}_-]+/u', '', $value);
        $value = mb_substr($value, 0, self::MAX_LENGTH);

        return mb_strlen($value) >= self::MIN_LENGTH ? $value : null;
    }

    /** Premier mot d'un nom complet, nettoyé (« Awa Koné » → « Awa »). */
    public static function fromName(?string $name): ?string
    {
        $words = preg_split('/\s+/u', trim(self::normalize((string) $name)), 2, PREG_SPLIT_NO_EMPTY);

        return self::clean($words[0] ?? null);
    }

    /** Pseudo de repli quand le nom ne donne rien d'utilisable. */
    public static function fallback(int $userId): string
    {
        return 'Joueur'.$userId;
    }

    /** Recompose les accents décomposés (NFD) pour qu'ils restent des lettres. */
    private static function normalize(string $value): string
    {
        if ($value === '' || ! class_exists(Normalizer::class)) {
            return $value;
        }

        return Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
    }
}
