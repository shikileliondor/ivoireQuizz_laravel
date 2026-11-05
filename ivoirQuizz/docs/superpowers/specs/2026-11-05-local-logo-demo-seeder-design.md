# Seeder local de logos de démonstration

## Intention

Fournir des données de test fiables pour l’écran Logos sans dépendre de fichiers téléversés, de liens externes ou de marques réelles.

## Conception

- Créer un seeder réservé aux environnements `local` et `testing`.
- Créer quatre marques fictives, chacune avec une image SVG autonome copiée sur le disque public.
- Créer une question active par marque avec une bonne réponse, trois mauvaises réponses et une source de démonstration renseignée.
- Rendre le seeder idempotent : une seconde exécution ne duplique ni questions ni réponses.
- Ne pas l’ajouter au `DatabaseSeeder` de production ; son lancement reste explicite.

## Approches considérées

- Logos réels téléchargés : rejeté, car dépendant du réseau, des droits de marque et de sources externes.
- Images générées au moment du seed : rejeté, car dépendant d’extensions graphiques PHP variables.
- Ressources SVG fictives versionnées : retenu, car déterministe, léger et testable.

