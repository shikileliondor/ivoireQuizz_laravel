# Logos à importer

Déposer ici un fichier PNG ou WebP par marque, nommé d'après elle : `Moov_Africa.png` donne la bonne réponse « Moov Africa » (casse et tirets gardés). Puis :

```bash
php artisan db:seed --force
```

- Mêmes règles que l'import de l'admin : 128 px minimum sur le plus grand côté, 2 Mo maximum. Un seul fichier refusé arrête l'import.
- Les 100 marques du catalogue sans fichier sont créées **sans image et inactives**. Pour en activer une : ajouter son image dans l'admin (édition de la question, ou import en lot avec le même nom de fichier), ou déposer le fichier ici et relancer le seeder.
- Difficulté reprise de `docs/questions/catalogue_100_marques_a_preparer.json`, sinon « easy ».
- Les mauvaises réponses sont tirées parmi les autres marques : il en faut au moins deux.
- Relançable : une marque déjà présente est ignorée ; une marque archivée dans l'admin n'est jamais réimportée.
- La source des images reste vide : la compléter dans l'admin (filtre « source manquante »).
- Ne déposer que des logos dont l'usage est autorisé.
