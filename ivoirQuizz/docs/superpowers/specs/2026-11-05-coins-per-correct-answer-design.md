# Pièces par bonne réponse

## Décision validée

Le panel expose un réglage global `coins_per_correct_answer`. Chaque mode crédite `bonnes réponses × valeur configurée`. Pour les niveaux et boss réussis, la récompense fixe du niveau reste un bonus cumulatif. La valeur est copiée dans la configuration de la session afin qu’une partie en cours conserve ses règles.

## Contraintes

- Tous les crédits passent par `RewardService` et sont historisés.
- Une partie échouée conserve les pièces gagnées par ses bonnes réponses, sans bonus fixe de niveau.
- Valeur par défaut : 1 pièce par bonne réponse ; l’admin peut choisir 0 à 1 000.

