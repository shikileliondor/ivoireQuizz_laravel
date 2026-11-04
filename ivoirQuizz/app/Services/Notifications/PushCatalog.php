<?php

namespace App\Services\Notifications;

/**
 * Types de notifications automatiques, avec leurs réglages et textes par défaut.
 * L'admin peut tout modifier (table push_automations) ; ces valeurs servent tant qu'il ne l'a pas fait.
 *
 * - « action » : réponse à ce qu'a fait une autre personne (ami, défi, admin). Toujours envoyée.
 * - « reminder » : relance pour faire jouer. Soumise au plafond quotidien et aux heures calmes.
 *
 * Une relance a soit un réglage « hour » (envoi quotidien), soit « every » (vérifiée toutes les N minutes).
 */
final class PushCatalog
{
    public const ACTION = 'action';

    public const REMINDER = 'reminder';

    /** Ligne de push_automations qui porte les réglages communs aux relances. */
    public const GLOBAL_KEY = 'reminders';

    public const GLOBAL_SETTINGS = [
        'max_per_day' => ['default' => 2, 'label' => 'Relances max. par joueur et par jour', 'min' => 1, 'max' => 10],
        'quiet_start' => ['default' => 21, 'label' => 'Heures calmes : début (h)', 'min' => 0, 'max' => 23],
        'quiet_end' => ['default' => 7, 'label' => 'Heures calmes : fin (h)', 'min' => 0, 'max' => 23],
    ];

    private const HOUR = ['label' => 'Heure d’envoi (h)', 'min' => 0, 'max' => 23, 'hint' => 'Heure d’Abidjan.'];

    private const TYPES = [
        'morning' => [
            'label' => 'Rappel du matin',
            'description' => 'Chaque matin, invite à jouer avec un texte adapté à la situation du joueur. Rien si le joueur a déjà joué dans la journée.',
            'category' => self::REMINDER,
            'data' => ['type' => 'daily_reminder'],
            'settings' => [
                'hour' => ['default' => 8] + self::HOUR,
                'discovery_days' => ['default' => 3, 'label' => 'Nouveau joueur pendant (jours)', 'min' => 1, 'max' => 30],
                'missed_after' => ['default' => 8, 'label' => '« Tu nous manques » après (jours d’absence)', 'min' => 2, 'max' => 60],
                'missed_every' => ['default' => 3, 'label' => '… puis un rappel tous les (jours)', 'min' => 1, 'max' => 30],
                'dormant_after' => ['default' => 31, 'label' => 'Joueur endormi après (jours d’absence)', 'min' => 7, 'max' => 180],
                'dormant_every' => ['default' => 7, 'label' => '… puis un rappel tous les (jours)', 'min' => 1, 'max' => 60],
                'stop_after' => ['default' => 90, 'label' => 'Arrêter après (jours d’absence)', 'min' => 14, 'max' => 365],
                'question_of_day' => ['default' => true, 'label' => 'Question du matin', 'type' => 'boolean', 'hint' => 'Les joueurs habituels reçoivent une vraie question du jeu, la même pour tous ce jour-là.'],
            ],
            'segments' => [
                'discovery' => [
                    'label' => 'Nouveau joueur',
                    'title' => 'Bienvenue dans IvoireQuizz 🇨🇮',
                    'bodies' => [
                        '{prenom}, ta première ville t’attend. Une partie de 2 minutes ?',
                        'Prêt à découvrir la Côte d’Ivoire, {prenom} ? Tes premières questions t’attendent.',
                    ],
                ],
                'streak' => [
                    'label' => 'Série en cours',
                    'title' => '🔥 Série de {serie} jours',
                    'bodies' => [
                        'Bien joué {prenom} ! Une partie aujourd’hui pour garder ta série en vie.',
                        'Ta série continue aujourd’hui, {prenom} ? Une seule partie suffit.',
                    ],
                ],
                'question' => [
                    'label' => 'Question du matin (joueur habituel)',
                    'title' => 'Question du matin ☀️',
                    'bodies' => ['{question}'],
                ],
                'daily' => [
                    'label' => 'Joueur habituel (sans question du matin)',
                    'title' => 'Bonjour {prenom} ☀️',
                    'bodies' => [
                        'De nouvelles questions sur la Côte d’Ivoire t’attendent.',
                        'Un petit quiz avec ton café ? Ta prochaine ville t’attend.',
                        'Combien de bonnes réponses aujourd’hui ? Viens tester tes connaissances.',
                    ],
                ],
                'missed' => [
                    'label' => 'Absent depuis quelques jours',
                    'title' => 'Tu nous manques, {prenom}',
                    'bodies' => [
                        'Ça fait {jours} jours ! La Côte d’Ivoire t’attend pour une partie.',
                        'Tes amis avancent au classement. Reviens jouer quelques minutes !',
                    ],
                ],
                'dormant' => [
                    'label' => 'Joueur endormi',
                    'title' => 'On ne t’oublie pas, {prenom} 🇨🇮',
                    'bodies' => ['De nouvelles questions sont arrivées. Viens voir où tu en es !'],
                ],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'serie' => 'jours de série', 'jours' => 'jours d’absence', 'question' => 'question du matin'],
            'sample' => ['serie' => 5, 'jours' => 10, 'question' => 'Quelle ville est surnommée la cité du Bélier ?'],
        ],
        'streak_risk' => [
            'label' => 'Série en danger',
            'description' => 'Le soir, prévient le joueur qui a une série en cours mais n’a pas encore joué dans la journée.',
            'category' => self::REMINDER,
            'data' => ['type' => 'streak'],
            'settings' => [
                'hour' => ['default' => 19] + self::HOUR,
                'min_streak' => ['default' => 2, 'label' => 'Série minimale (jours)', 'min' => 1, 'max' => 30],
            ],
            'segments' => [
                'default' => [
                    'label' => 'Message',
                    'title' => 'Ta série est en danger 🔥',
                    'bodies' => [
                        '{prenom}, ta série de {serie} jours s’arrête à minuit. Une partie suffit !',
                        'Plus que quelques heures pour sauver ta série de {serie} jours !',
                    ],
                ],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'serie' => 'jours de série'],
            'sample' => ['serie' => 12],
        ],
        'lives_full' => [
            'label' => 'Vies rechargées',
            'description' => 'Prévient le joueur tombé à 0 vie quand toutes ses vies sont revenues.',
            'category' => self::REMINDER,
            'every' => 10,
            'data' => ['type' => 'lives'],
            'settings' => [],
            'segments' => [
                'default' => [
                    'label' => 'Message',
                    'title' => 'Tes vies sont rechargées ⚡',
                    'bodies' => ['{prenom}, tes {vies} vies t’attendent. On reprend où tu t’es arrêté ?'],
                ],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'vies' => 'nombre de vies'],
            'sample' => ['vies' => 5],
        ],
        'chest_reminder' => [
            'label' => 'Coffre à ouvrir',
            'description' => 'Rappelle au joueur un coffre gagné mais pas encore ouvert. Une seule fois par coffre.',
            'category' => self::REMINDER,
            'data' => ['type' => 'chests'],
            'settings' => [
                'hour' => ['default' => 17] + self::HOUR,
                'after_hours' => ['default' => 24, 'label' => 'Coffre disponible depuis (heures)', 'min' => 1, 'max' => 168],
            ],
            'segments' => [
                'default' => [
                    'label' => 'Message',
                    'title' => 'Un coffre t’attend 🎁',
                    'bodies' => ['{prenom}, ton {coffre} n’est pas encore ouvert. Qu’y a-t-il dedans ?'],
                ],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'coffre' => 'nom du coffre'],
            'sample' => ['coffre' => 'Coffre d’or'],
        ],
        'challenge_reminder' => [
            'label' => 'Défi sans réponse',
            'description' => 'Relance l’adversaire qui n’a pas joué un défi reçu. Une seule fois par défi.',
            'category' => self::REMINDER,
            'every' => 60,
            'data' => ['type' => 'challenge'],
            'settings' => [
                'after_hours' => ['default' => 24, 'label' => 'Défi sans réponse depuis (heures)', 'min' => 1, 'max' => 144],
            ],
            'segments' => [
                'default' => [
                    'label' => 'Message',
                    'title' => '{ami} t’attend ⚔️',
                    'bodies' => ['Tu n’as pas encore joué son défi. Montre-lui qui est le plus fort !'],
                ],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'ami' => 'nom du lanceur du défi'],
            'sample' => ['ami' => 'Awa'],
        ],
        'season_ending' => [
            'label' => 'Fin de saison de ligue',
            'description' => 'Prévient les membres d’une ligue peu avant la fin de la saison. Une seule fois par saison.',
            'category' => self::REMINDER,
            'every' => 60,
            'data' => ['type' => 'league'],
            'settings' => [
                'hours_before' => ['default' => 24, 'label' => 'Prévenir avant la fin (heures)', 'min' => 1, 'max' => 72],
            ],
            'segments' => [
                'default' => [
                    'label' => 'Message',
                    'title' => 'Fin de saison dans {heures} h 🏆',
                    'bodies' => ['Dernière ligne droite en {ligue}, {prenom} ! Une partie pour finir plus haut ?'],
                ],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'ligue' => 'nom de la ligue', 'heures' => 'heures restantes'],
            'sample' => ['ligue' => 'Ligue Or', 'heures' => 24],
        ],
        'friend_request' => [
            'label' => 'Demande d’ami',
            'description' => 'Un joueur t’envoie une demande d’ami.',
            'category' => self::ACTION,
            'data' => ['type' => 'friend_request'],
            'settings' => [],
            'segments' => [
                'default' => ['label' => 'Message', 'title' => 'Nouvelle demande d’ami', 'bodies' => ['{ami} veut devenir ton ami sur IvoireQuizz.']],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'ami' => 'nom du demandeur'],
            'sample' => ['ami' => 'Awa'],
        ],
        'friend_accepted' => [
            'label' => 'Ami accepté',
            'description' => 'Un joueur accepte ta demande d’ami.',
            'category' => self::ACTION,
            'data' => ['type' => 'friend_accepted'],
            'settings' => [],
            'segments' => [
                'default' => ['label' => 'Message', 'title' => 'Demande acceptée', 'bodies' => ['{ami} a accepté ta demande d’ami.']],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'ami' => 'nom de l’ami'],
            'sample' => ['ami' => 'Awa'],
        ],
        'challenge_received' => [
            'label' => 'Défi reçu',
            'description' => 'Un ami te lance un défi.',
            'category' => self::ACTION,
            'data' => ['type' => 'challenge'],
            'settings' => [],
            'segments' => [
                'default' => ['label' => 'Message', 'title' => 'Nouveau défi ! ⚔️', 'bodies' => ['{ami} te défie sur IvoireQuizz. Code : {code}']],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'ami' => 'nom du lanceur', 'code' => 'code du défi'],
            'sample' => ['ami' => 'Awa', 'code' => 'A1B2C3D4'],
        ],
        'challenge_result' => [
            'label' => 'Résultat de défi',
            'description' => 'L’autre joueur a terminé le défi : chacun connaît le résultat.',
            'category' => self::ACTION,
            'data' => ['type' => 'challenge'],
            'settings' => [],
            'segments' => [
                'win' => ['label' => 'Victoire', 'title' => 'Victoire ! 🏆', 'bodies' => ['{ami} a joué ton défi : tu gagnes {score_toi} à {score_ami}.']],
                'lose' => ['label' => 'Défaite', 'title' => 'Défaite… 😅', 'bodies' => ['{ami} a joué ton défi et gagne {score_ami} à {score_toi}. Prends ta revanche !']],
                'draw' => ['label' => 'Égalité', 'title' => 'Égalité 🤝', 'bodies' => ['{ami} a joué ton défi : égalité, {score_toi} partout.']],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'ami' => 'nom de l’adversaire', 'score_toi' => 'ton score', 'score_ami' => 'score de l’adversaire'],
            'sample' => ['ami' => 'Awa', 'score_toi' => 80, 'score_ami' => 60],
        ],
        'report_fixed' => [
            'label' => 'Signalement corrigé',
            'description' => 'Un admin passe un signalement de question en « Corrigé » : le joueur qui l’a signalé est remercié.',
            'category' => self::ACTION,
            'data' => ['type' => 'report_fixed'],
            'settings' => [],
            'segments' => [
                'default' => ['label' => 'Message', 'title' => 'Merci pour ton signalement 🙏', 'bodies' => ['{prenom}, la question que tu as signalée a été corrigée.']],
            ],
            'variables' => ['prenom' => 'prénom du joueur'],
            'sample' => [],
        ],
        'admin_gift' => [
            'label' => 'Cadeau de l’équipe',
            'description' => 'Pièces ou gemmes ajoutées depuis la fiche joueur, quand la case « Prévenir le joueur » est cochée.',
            'category' => self::ACTION,
            'data' => ['type' => 'gift'],
            'settings' => [],
            'segments' => [
                'default' => ['label' => 'Message', 'title' => 'Cadeau reçu 🎁', 'bodies' => ['Tu as reçu {montant} {monnaie} de l’équipe IvoireQuizz.']],
            ],
            'variables' => ['prenom' => 'prénom du joueur', 'montant' => 'montant', 'monnaie' => 'pièces ou gemmes'],
            'sample' => ['montant' => 100, 'monnaie' => 'pièces'],
        ],
    ];

    /** @return array<string, array> */
    public static function all(): array
    {
        return self::TYPES;
    }

    public static function get(string $key): ?array
    {
        return self::TYPES[$key] ?? null;
    }

    /** @return array<string, array> */
    public static function reminders(): array
    {
        return array_filter(self::TYPES, fn (array $type) => $type['category'] === self::REMINDER);
    }

    /** @return array<string, array> */
    public static function actions(): array
    {
        return array_filter(self::TYPES, fn (array $type) => $type['category'] === self::ACTION);
    }
}
