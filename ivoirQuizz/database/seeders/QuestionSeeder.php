<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupération de toutes les catégories
        $categories = [
            'Histoire & Politique' => Category::where('name', 'Histoire & Politique')->first()?->id,
            'Géographie' => Category::where('name', 'Géographie')->first()?->id,
            'Gastronomie & Traditions' => Category::where('name', 'Gastronomie & Traditions')->first()?->id,
            'Culture & Arts' => Category::where('name', 'Culture & Arts')->first()?->id,
            'Sport ivoirien' => Category::where('name', 'Sport ivoirien')->first()?->id,
            'Économie & Développement' => Category::where('name', 'Économie & Développement')->first()?->id,
            'Langues & Peuples' => Category::where('name', 'Langues & Peuples')->first()?->id,
            'Personnalités ivoiriennes' => Category::where('name', 'Personnalités ivoiriennes')->first()?->id,
            'Musique ivoirienne' => Category::where('name', 'Musique ivoirienne')->first()?->id,
            'Villes & Régions' => Category::where('name', 'Villes & Régions')->first()?->id,
            'Patrimoine & Tourisme' => Category::where('name', 'Patrimoine & Tourisme')->first()?->id,
            'Éducation & Société' => Category::where('name', 'Éducation & Société')->first()?->id,
            'Christianisme en Côte d’Ivoire' => Category::where('name', 'Christianisme en Côte d’Ivoire')->first()?->id,
            'Islam en Côte d’Ivoire' => Category::where('name', 'Islam en Côte d’Ivoire')->first()?->id,
        ];

        // Vérification que toutes les catégories existent
        foreach ($categories as $name => $id) {
            if (! $id) {
                $this->command?->error("La catégorie '$name' est manquante. Exécutez CategorySeeder avant QuestionSeeder.");
                return;
            }
        }

        $questionsByCategory = [
            // ==================== HISTOIRE & POLITIQUE (15 questions) ====================
            $categories['Histoire & Politique'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"En quelle année la Côte d'Ivoire a-t-elle proclamé son indépendance ?",'explanation'=>"Indépendance le 7 août 1960.",'options'=>[['text'=>'1958','correct'=>false],['text'=>'1960','correct'=>true],['text'=>'1962','correct'=>false],['text'=>'1965','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Qui a été le premier président de la Côte d'Ivoire ?",'explanation'=>"Félix Houphouët-Boigny (1960-1993).",'options'=>[['text'=>'Laurent Gbagbo','correct'=>false],['text'=>'Henri Konan Bédié','correct'=>false],['text'=>'Félix Houphouët-Boigny','correct'=>true],['text'=>'Alassane Ouattara','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle est la capitale politique de la Côte d'Ivoire ?",'explanation'=>"Yamoussoukro depuis 1983.",'options'=>[['text'=>'Abidjan','correct'=>false],['text'=>'Bouaké','correct'=>false],['text'=>'Yamoussoukro','correct'=>true],['text'=>'San-Pédro','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel président a succédé à Félix Houphouët-Boigny ?",'explanation'=>"Henri Konan Bédié (1993-1999).",'options'=>[['text'=>'Laurent Gbagbo','correct'=>false],['text'=>'Henri Konan Bédié','correct'=>true],['text'=>'Robert Guéï','correct'=>false],['text'=>'Alassane Ouattara','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Félix Houphouët-Boigny a gouverné pendant plus de 30 ans.",'explanation'=>"Vrai. 33 ans (1960-1993).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Abidjan est la capitale officielle de la Côte d'Ivoire.",'explanation'=>"Faux. Yamoussoukro est la capitale officielle.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel accord a mis fin à la crise de 2002-2007 ?",'explanation'=>"Accord de Ouagadougou (2007).",'options'=>[['text'=>'Accord de Marcoussis','correct'=>false],['text'=>'Accord de Ouagadougou','correct'=>true],['text'=>'Accord de Pretoria','correct'=>false],['text'=>'Accord de Linas-Marcoussis','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Qui a été président après la crise post-électorale de 2010-2011 ?",'explanation'=>"Alassane Ouattara (2011).",'options'=>[['text'=>'Laurent Gbagbo','correct'=>false],['text'=>'Alassane Ouattara','correct'=>true],['text'=>'Henri Konan Bédié','correct'=>false],['text'=>'Pascal Affi N\'Guessan','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"De quelle couleur est la bande gauche du drapeau ivoirien ?",'explanation'=>"Orange (savane du nord).",'options'=>[['text'=>'Vert','correct'=>false],['text'=>'Blanc','correct'=>false],['text'=>'Orange','correct'=>true],['text'=>'Rouge','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Côte d'Ivoire a connu deux coups d'État militaires.",'explanation'=>"Vrai. 1999 (Guéï) et 2002 (tentative réussie partielle).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel est le nom du parlement ivoirien ?",'explanation'=>"Assemblée Nationale + Sénat (bicaméral depuis 2016).",'options'=>[['text'=>'Le Sénat National','correct'=>false],['text'=>'L\'Assemblée Fédérale','correct'=>false],['text'=>'L\'Assemblée Nationale','correct'=>true],['text'=>'Le Congrès Ivoirien','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel parti politique a fondé Félix Houphouët-Boigny ?",'explanation'=>"PDCI-RDA (Parti Démocratique de Côte d'Ivoire).",'options'=>[['text'=>'FPI','correct'=>false],['text'=>'PDCI-RDA','correct'=>true],['text'=>'RDR','correct'=>false],['text'=>'RHDP','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le 7 août est la fête nationale ivoirienne.",'explanation'=>"Vrai. Célébration de l'indépendance.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Qui était le premier Premier ministre de Côte d'Ivoire ?",'explanation'=>"Félix Houphouët-Boigny (poste créé en 1959).",'options'=>[['text'=>'Auguste Denise','correct'=>false],['text'=>'Félix Houphouët-Boigny','correct'=>true],['text'=>'Alassane Ouattara','correct'=>false],['text'=>'Daniel Kablan Duncan','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Constitution de 2016 a créé le Sénat.",'explanation'=>"Vrai. C'est la 3e République ivoirienne.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== GÉOGRAPHIE (15 questions) ====================
            $categories['Géographie'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Avec combien de pays la Côte d'Ivoire partage-t-elle ses frontières ?",'explanation'=>"5 pays : Ghana, Burkina, Mali, Guinée, Liberia.",'options'=>[['text'=>'3 pays','correct'=>false],['text'=>'4 pays','correct'=>false],['text'=>'5 pays','correct'=>true],['text'=>'6 pays','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle est la deuxième plus grande ville de Côte d'Ivoire ?",'explanation'=>"Bouaké (environ 1 million d'habitants).",'options'=>[['text'=>'Yamoussoukro','correct'=>false],['text'=>'San-Pédro','correct'=>false],['text'=>'Bouaké','correct'=>true],['text'=>'Daloa','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le plus grand port de Côte d'Ivoire ?",'explanation'=>"Port d'Abidjan (plus de 20 millions de tonnes/an).",'options'=>[['text'=>'Port de Sassandra','correct'=>false],['text'=>'Port d\'Abidjan','correct'=>true],['text'=>'Port de San-Pédro','correct'=>false],['text'=>'Port de Grand-Bassam','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Côte d'Ivoire est un pays enclavé.",'explanation'=>"Faux. Elle a 550 km de côtes.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le point culminant de la Côte d'Ivoire ?",'explanation'=>"Mont Nimba (1752 m).",'options'=>[['text'=>'Mont Nimba','correct'=>true],['text'=>'Mont Tonkoui','correct'=>false],['text'=>'Mont Péko','correct'=>false],['text'=>'Mont Sangbé','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quel fleuve traverse Yamoussoukro ?",'explanation'=>"Le Bandama (plus long fleuve ivoirien).",'options'=>[['text'=>'Le Bandama','correct'=>true],['text'=>'Le Sassandra','correct'=>false],['text'=>'La Comoé','correct'=>false],['text'=>'Le Cavally','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le Parc National de Taï est classé à l'UNESCO.",'explanation'=>"Vrai. Patrimoine mondial depuis 1982.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle lagune borde Abidjan ?",'explanation'=>"Lagune Ébrié (566 km²).",'options'=>[['text'=>'Lagune Aby','correct'=>false],['text'=>'Lagune Ébrié','correct'=>true],['text'=>'Lagune Tendo','correct'=>false],['text'=>'Lagune Aghien','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel fleuve forme la frontière avec le Liberia ?",'explanation'=>"Le Cavally (environ 320 km de frontière).",'options'=>[['text'=>'Le Bandama','correct'=>false],['text'=>'La Comoé','correct'=>false],['text'=>'Le Cavally','correct'=>true],['text'=>'Le Sassandra','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Combien de districts compte la Côte d'Ivoire ?",'explanation'=>"14 districts (dont Abidjan et Yamoussoukro).",'options'=>[['text'=>'10 districts','correct'=>false],['text'=>'12 districts','correct'=>false],['text'=>'14 districts','correct'=>true],['text'=>'16 districts','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle ville est surnommée 'la ville aux collines' ?",'explanation'=>"Man (région montagneuse de l'ouest).",'options'=>[['text'=>'Bouaké','correct'=>false],['text'=>'Daloa','correct'=>false],['text'=>'Man','correct'=>true],['text'=>'Odienné','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"San-Pédro est le deuxième port ivoirien.",'explanation'=>"Vrai. Premier port cacaoyer mondial.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle est la commune la plus peuplée d'Abidjan ?",'explanation'=>"Yopougon (plus de 2 millions d'habitants).",'options'=>[['text'=>'Cocody','correct'=>false],['text'=>'Plateau','correct'=>false],['text'=>'Yopougon','correct'=>true],['text'=>'Abobo','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel est le plus long fleuve de Côte d'Ivoire ?",'explanation'=>"Bandama (1050 km).",'options'=>[['text'=>'Bandama','correct'=>true],['text'=>'Comoé','correct'=>false],['text'=>'Sassandra','correct'=>false],['text'=>'Cavally','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Côte d'Ivoire possède des frontières maritimes.",'explanation'=>"Vrai. Zone Économique Exclusive (ZEE).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== GASTRONOMIE & TRADITIONS (15 questions) ====================
            $categories['Gastronomie & Traditions'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quel est le plat national le plus emblématique ?",'explanation'=>"L'attiéké poisson (semoule de manioc fermenté).",'options'=>[['text'=>'Thiéboudienne','correct'=>false],['text'=>'Foutou bangui','correct'=>false],['text'=>'Attiéké poisson','correct'=>true],['text'=>'Kedjenou','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"De quoi est fait l'attiéké ?",'explanation'=>"Manioc fermenté et râpé.",'options'=>[['text'=>'Mil','correct'=>false],['text'=>'Manioc fermenté','correct'=>true],['text'=>'Maïs','correct'=>false],['text'=>'Riz','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Qu'est-ce que le kedjenou ?",'explanation'=>"Ragoût mijoté en canari (spécialité Baoulé).",'options'=>[['text'=>'Dessert','correct'=>false],['text'=>'Boisson','correct'=>false],['text'=>'Ragoût mijoté','correct'=>true],['text'=>'Sauce pimentée','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le garba est à base d'attiéké et de thon.",'explanation'=>"Vrai. Plat populaire d'Abidjan.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle boisson traditionnelle est faite à partir de palme ?",'explanation'=>"Le bangui (vin de palme).",'options'=>[['text'=>'Bangui','correct'=>true],['text'=>'Tchapalo','correct'=>false],['text'=>'Bisap','correct'=>false],['text'=>'Gnamakoudji','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quel peuple est réputé pour la danse des masques ?",'explanation'=>"Les Dan (Yacouba) de l'ouest.",'options'=>[['text'=>'Baoulé','correct'=>false],['text'=>'Dioula','correct'=>false],['text'=>'Dan (Yacouba)','correct'=>true],['text'=>'Agni','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Qu'est-ce que le foutou ?",'explanation'=>"Pâte pilée (banane plantain ou igname).",'options'=>[['text'=>'Sauce','correct'=>false],['text'=>'Pâte pilée','correct'=>true],['text'=>'Dessert','correct'=>false],['text'=>'Boisson','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le coupé-décalé est un genre musical ivoirien.",'explanation'=>"Vrai. Né à Paris dans les années 2000.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel tissu est associé aux cérémonies royales ?",'explanation'=>"Le kente (peuples Akan).",'options'=>[['text'=>'Bogolan','correct'=>false],['text'=>'Kente','correct'=>true],['text'=>'Bazin','correct'=>false],['text'=>'Wax','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quelle est la fête Baoulé des premières récoltes ?",'explanation'=>"L'Adjanou (hommage aux ancêtres).",'options'=>[['text'=>'Abissa','correct'=>false],['text'=>'Dipri','correct'=>false],['text'=>'Adjanou','correct'=>true],['text'=>'Festima','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le placali est à base de manioc.",'explanation'=>"Vrai. Pâte gélatineuse.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle fête Adjoukrou est célébrée à Jacqueville ?",'explanation'=>"Le Dipri (rites de purification).",'options'=>[['text'=>'Abissa','correct'=>false],['text'=>'Dipri','correct'=>true],['text'=>'Adjanou','correct'=>false],['text'=>'Popo Carneval','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Comment appelle-t-on le petit restaurant populaire ?",'explanation'=>"Le maquis (lieu de vie sociale).",'options'=>[['text'=>'Maquis','correct'=>true],['text'=>'Grin','correct'=>false],['text'=>'Cabaret','correct'=>false],['text'=>'Garba','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le zouglou est né dans les universités.",'explanation'=>"Vrai. Années 1990 à Abidjan.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel instrument est emblématique du nord ?",'explanation'=>"La kora (instrument à cordes des griots).",'options'=>[['text'=>'Djembé','correct'=>false],['text'=>'Kora','correct'=>true],['text'=>'Balafon','correct'=>false],['text'=>'Tam-tam','correct'=>false]]],
            ],

            // ==================== CULTURE & ARTS (12 questions) ====================
            $categories['Culture & Arts'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quel festival des masques se déroule à Man ?",'explanation'=>"FESTIMA (Festival des Masques).",'options'=>[['text'=>'FESPACO','correct'=>false],['text'=>'FESTIMA','correct'=>true],['text'=>'MASA','correct'=>false],['text'=>'JAZZABIDJAN','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Côte d'Ivoire a une basilique à Yamoussoukro.",'explanation'=>"Vrai. Basilique Notre-Dame de la Paix.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel peintre ivoirien est connu pour ses petites formes rondes ?",'explanation'=>"Frédéric Bruly Bouabré (artiste légendaire).",'options'=>[['text'=>'Frédéric Bruly Bouabré','correct'=>true],['text'=>'Tiemoko Coulibaly','correct'=>false],['text'=>'Yacouba Touré','correct'=>false],['text'=>'Ouattara Watts','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le FESPACO se déroule à Abidjan.",'explanation'=>"Faux. C'est au Burkina Faso.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle est la plus grande salle de spectacle d'Abidjan ?",'explanation'=>"Palais de la Culture de Treichville.",'options'=>[['text'=>'Palais de la Culture','correct'=>true],['text'=>'Théâtre Municipal','correct'=>false],['text'=>'Institut Français','correct'=>false],['text'=>'CCF','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le Musée des Civilisations se trouve à Abidjan.",'explanation'=>"Vrai. Au Plateau.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel écrivain ivoirien a écrit 'Les Soleils des indépendances' ?",'explanation'=>"Ahmadou Kourouma (grand écrivain).",'options'=>[['text'=>'Bernard Dadié','correct'=>false],['text'=>'Ahmadou Kourouma','correct'=>true],['text'=>'Mariama Bâ','correct'=>false],['text'=>'Véronique Tadjo','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le CARA est un festival de théâtre à Yopougon.",'explanation'=>"Vrai. Carrefour des Arts.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le nom du célèbre ballet ivoirien ?",'explanation'=>"Ballet National de Côte d'Ivoire.",'options'=>[['text'=>'Ballet National','correct'=>true],['text'=>'Danse des Échassiers','correct'=>false],['text'=>'Ballet Zaouli','correct'=>false],['text'=>'Ballet Adjoukrou','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quel site historique de Grand-Bassam est classé UNESCO ?",'explanation'=>"Quartier France (ancienne capitale coloniale).",'options'=>[['text'=>'Quartier France','correct'=>true],['text'=>'Cathédrale','correct'=>false],['text'=>'Palais du Gouverneur','correct'=>false],['text'=>'Musée de la Femme','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le cinéma ivoirien existe depuis 1960.",'explanation'=>"Vrai. 'Mouna ou le Rêve d'un Artiste' (1969).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Qui est le plus célèbre sculpteur ivoirien ?",'explanation'=>"Christian Lattier (pionnier de l'art moderne).",'options'=>[['text'=>'Christian Lattier','correct'=>true],['text'=>'Paul Kodjo','correct'=>false],['text'=>'James Houra','correct'=>false],['text'=>'Souleymane Keita','correct'=>false]]],
            ],

            // ==================== SPORT IVOIRIEN (12 questions) ====================
            $categories['Sport ivoirien'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Combien de CAN la Côte d'Ivoire a-t-elle remportées ?",'explanation'=>"3 (1992, 2015, 2024).",'options'=>[['text'=>'2','correct'=>false],['text'=>'3','correct'=>true],['text'=>'4','correct'=>false],['text'=>'5','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Didier Drogba est le meilleur buteur des Éléphants.",'explanation'=>"Vrai. 65 buts en 105 sélections.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel club ivoirien est le plus titré en championnat ?",'explanation'=>"ASEC Mimosas (plus de 25 titres).",'options'=>[['text'=>'ASEC Mimosas','correct'=>true],['text'=>'Africa Sports','correct'=>false],['text'=>'Stade d\'Abidjan','correct'=>false],['text'=>'Séwé Sports','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Côte d'Ivoire a participé à 4 Coupes du monde.",'explanation'=>"Faux. 2006, 2010, 2014 (3 participations).",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Qui a marqué le but de la victoire en CAN 2015 ?",'explanation'=>"Wilfried Bony (finale contre le Ghana).",'options'=>[['text'=>'Didier Drogba','correct'=>false],['text'=>'Wilfried Bony','correct'=>true],['text'=>'Gervinho','correct'=>false],['text'=>'Yaya Touré','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Côte d'Ivoire a accueilli la CAN 1984.",'explanation'=>"Vrai. Et l'a remportée.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel athlète ivoirien a remporté l'or au 400m haies ?",'explanation'=>"Gabriel Tiacoh (argent en 1984, pas d'or).",'options'=>[['text'=>'Gabriel Tiacoh','correct'=>false],['text'=>'Murielle Ahouré','correct'=>false],['text'=>'Marie-Josée Ta Lou','correct'=>false],['text'=>'Aucun (médaille d\'argent)','correct'=>true]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"ASEC Mimosas a formé Yaya Touré.",'explanation'=>"Vrai. Académie Mimosifcom.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel gardien ivoirien a été élu meilleur gardien d'Afrique ?",'explanation'=>"Alain Gouaméné (CAN 1992).",'options'=>[['text'=>'Boubacar Barry','correct'=>false],['text'=>'Alain Gouaméné','correct'=>true],['text'=>'Copa','correct'=>false],['text'=>'Manden Koné','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Côte d'Ivoire a une équipe nationale de rugby.",'explanation'=>"Vrai. Moins médiatisée mais active.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Qui est le meilleur buteur de l'histoire du championnat ivoirien ?",'explanation'=>"Niangbo (ASEC/Séwé).",'options'=>[['text'=>'Niangbo','correct'=>true],['text'=>'Drogba','correct'=>false],['text'=>'Traoré','correct'=>false],['text'=>'Doumbia','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Les Éléphants est le surnom de l'équipe nationale.",'explanation'=>"Vrai. Symbole animal national.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== ÉCONOMIE & DÉVELOPPEMENT (12 questions) ====================
            $categories['Économie & Développement'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle est la première ressource d'exportation ?",'explanation'=>"Cacao (environ 40% mondial).",'options'=>[['text'=>'Café','correct'=>false],['text'=>'Cacao','correct'=>true],['text'=>'Noix de cajou','correct'=>false],['text'=>'Caoutchouc','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Abidjan concentre plus de 50% du PIB national.",'explanation'=>"Vrai. Capitale économique.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le principal port minéralier ?",'explanation'=>"San-Pédro (cacao, bois, minerais).",'options'=>[['text'=>'Abidjan','correct'=>false],['text'=>'San-Pédro','correct'=>true],['text'=>'Sassandra','correct'=>false],['text'=>'Grand-Bassam','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le café robusta est une production historique.",'explanation'=>"Vrai. Introduit par les colons.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle est la monnaie de la Côte d'Ivoire ?",'explanation'=>"Franc CFA (FCFA).",'options'=>[['text'=>'Franc guinéen','correct'=>false],['text'=>'Franc CFA','correct'=>true],['text'=>'Cedi','correct'=>false],['text'=>'Naira','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Côte d'Ivoire est membre de la Zone Franc.",'explanation'=>"Vrai. Depuis l'indépendance.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle est la 2e culture d'exportation ?",'explanation'=>"Anacarde (noix de cajou).",'options'=>[['text'=>'Hévéa','correct'=>false],['text'=>'Café','correct'=>false],['text'=>'Noix de cajou','correct'=>true],['text'=>'Palmier à huile','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Côte d'Ivoire est leader mondial du cacao.",'explanation'=>"Vrai. Plus grand producteur.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel est le PIB nominal de la Côte d'Ivoire ?",'explanation'=>"Environ 70 milliards USD (2023).",'options'=>[['text'=>'50 Mds','correct'=>false],['text'=>'70 Mds','correct'=>true],['text'=>'100 Mds','correct'=>false],['text'=>'120 Mds','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le Conseil Café-Cacao régule la filière.",'explanation'=>"Vrai. Depuis 2012.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel secteur est le 2e contributeur au PIB ?",'explanation'=>"Bâtiment et travaux publics (BTP).",'options'=>[['text'=>'Agriculture','correct'=>false],['text'=>'Services','correct'=>false],['text'=>'BTP','correct'=>true],['text'=>'Industrie','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le District Autonome d'Abidjan a un budget propre.",'explanation'=>"Vrai. Collectivité territoriale.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== LANGUES & PEUPLES (12 questions) ====================
            $categories['Langues & Peuples'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle langue nationale est parlée dans le nord ?",'explanation'=>"Dioula (langue véhiculaire).",'options'=>[['text'=>'Baoulé','correct'=>false],['text'=>'Dioula','correct'=>true],['text'=>'Bété','correct'=>false],['text'=>'Sénoufo','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Côte d'Ivoire compte plus de 60 langues locales.",'explanation'=>"Vrai. Environ 80 langues.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel peuple est réputé pour le tissu Kente ?",'explanation'=>"Les Agni (et Baoulé/Akan).",'options'=>[['text'=>'Agni','correct'=>true],['text'=>'Dioula','correct'=>false],['text'=>'Yacouba','correct'=>false],['text'=>'Krou','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Les Sénoufo viennent du sud forestier.",'explanation'=>"Faux. Viennent du nord.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le groupe ethnique majoritaire ?",'explanation'=>"Akan (Baoulé, Agni, etc.).",'options'=>[['text'=>'Akan','correct'=>true],['text'=>'Krou','correct'=>false],['text'=>'Mandé Nord','correct'=>false],['text'=>'Mandé Sud','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le français est la seule langue officielle.",'explanation'=>"Vrai. Depuis l'indépendance.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel peuple est connu pour la danse Zaouli ?",'explanation'=>"Les Guro (centre-ouest).",'options'=>[['text'=>'Baoulé','correct'=>false],['text'=>'Guro','correct'=>true],['text'=>'Bété','correct'=>false],['text'=>'Yacouba','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le nouchi est un argot ivoirien.",'explanation'=>"Vrai. Très populaire à Abidjan.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel peuple a fondé le royaume du Kénédougou ?",'explanation'=>"Les Sénoufo (au nord).",'options'=>[['text'=>'Koulango','correct'=>false],['text'=>'Sénoufo','correct'=>true],['text'=>'Lobi','correct'=>false],['text'=>'Dioula','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Les Baoulé sont originaires du Ghana.",'explanation'=>"Vrai. Migration au XVIIIe siècle.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle langue est parlée par les Bété ?",'explanation'=>"Bété (langue krou).",'options'=>[['text'=>'Krou','correct'=>true],['text'=>'Akan','correct'=>false],['text'=>'Mandé','correct'=>false],['text'=>'Gur','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le peuple Adjoukrou vit près de Dabou.",'explanation'=>"Vrai. Région de Jacqueville.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== PERSONNALITÉS IVOIRIENNES (12 questions) ====================
            $categories['Personnalités ivoiriennes'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Qui a fondé le magazine 'Fraternité Matin' ?",'explanation'=>"Félix Houphouët-Boigny.",'options'=>[['text'=>'Henri Konan Bédié','correct'=>false],['text'=>'Félix Houphouët-Boigny','correct'=>true],['text'=>'Alassane Ouattara','correct'=>false],['text'=>'Laurent Gbagbo','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Henri Konan Bédié est à l'origine de l'ivoirité.",'explanation'=>"Vrai. Concept politique des années 1990.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle artiste était surnommée 'Mélodie' ?",'explanation'=>"Aïcha Koné (reine du coupé-décalé).",'options'=>[['text'=>'Aïcha Koné','correct'=>true],['text'=>'Fally Ipupa','correct'=>false],['text'=>'Nabintou Diakité','correct'=>false],['text'=>'Joséphine Dago','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel scientifique ivoirien est spécialiste du VIH ?",'explanation'=>"Pr Luc Montagnier (co-découvreur, non ivoirien). Aucun.",'options'=>[['text'=>'Pr Dosso','correct'=>false],['text'=>'Pr Eholié','correct'=>true],['text'=>'Pr Yapo','correct'=>false],['text'=>'Pr Kouadio','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Alpha Blondy est un chanteur reggae ivoirien.",'explanation'=>"Vrai. Internationalement connu.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Qui a été la première femme pilote ivoirienne ?",'explanation'=>"Maître Rose Délima (Avocate, pas pilote).",'options'=>[['text'=>'Rose Délima','correct'=>false],['text'=>'Clarisse Kacou','correct'=>false],['text'=>'Bernadette Aka','correct'=>false],['text'=>'Kacou (pilote privée)','correct'=>true]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Yaya Touré a été élu Ballon d'Or africain 4 fois.",'explanation'=>"Faux. 4 fois (2011, 2012, 2013, 2014).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel chef d'entreprise dirige le Groupe NSIA ?",'explanation'=>"Jean Kacou Diagou.",'options'=>[['text'=>'Jean Kacou Diagou','correct'=>true],['text'=>'Pascal Ake','correct'=>false],['text'=>'Mamadou Coulibaly','correct'=>false],['text'=>'Koné Dossongui','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Marie-Josée Ta Lou est championne du monde.",'explanation'=>"Faux. Médaillée en championnats du monde, pas championne.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel premier ministre a créé le Fonds d'Aide aux Jeunes ?",'explanation'=>"Charles Konan Banny (2005-2007).",'options'=>[['text'=>'Charles Konan Banny','correct'=>true],['text'=>'Seydou Diarra','correct'=>false],['text'=>'Pascal Affi N\'Guessan','correct'=>false],['text'=>'Daniel Kablan Duncan','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Frédéric Bruly Bouabré est poète et artiste.",'explanation'=>"Vrai. Inventeur d'une écriture.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle femme politique a été ministre de l'Éducation ?",'explanation'=>"Kandia Camara (aussi ministre des Affaires étrangères).",'options'=>[['text'=>'Kandia Camara','correct'=>true],['text'=>'Raymonde Goudou','correct'=>false],['text'=>'Jeanne Peuhmond','correct'=>false],['text'=>'Adjoua N\'Doli','correct'=>false]]],
            ],

            // ==================== MUSIQUE IVOIRIENNE (12 questions) ====================
            $categories['Musique ivoirienne'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Qui est le précurseur du coupé-décalé ?",'explanation'=>"Douk Saga (2003).",'options'=>[['text'=>'Douk Saga','correct'=>true],['text'=>'DJ Arafat','correct'=>false],['text'=>'Serge Beynaud','correct'=>false],['text'=>'Debordo Leekunfa','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le zouglou est né dans les universités.",'explanation'=>"Vrai. Années 1990.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel chanteur a popularisé 'Djessimidjé' ?",'explanation'=>"Tiken Jah Fakoly (reggae engagé).",'options'=>[['text'=>'Tiken Jah Fakoly','correct'=>true],['text'=>'Alpha Blondy','correct'=>false],['text'=>'Souleymane Diamanka','correct'=>false],['text'=>'Nash','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Alpha Blondy est surnommé le 'Bob Marley africain'.",'explanation'=>"Vrai. Reconnaissance internationale.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le premier groupe de zouglou célèbre ?",'explanation'=>"Les Pionniers du Zouglou (Yabongo).",'options'=>[['text'=>'Les Pionniers','correct'=>true],['text'=>'Magic System','correct'=>false],['text'=>'Espoir 2000','correct'=>false],['text'=>'Glory Orédia','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Magic System vient d'Abidjan.",'explanation'=>"Faux. Vient de Daloa.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel instrument traditionnel joue Tiken Jah Fakoly ?",'explanation'=>"Le balafon (mélange reggae/tradi).",'options'=>[['text'=>'Djembé','correct'=>false],['text'=>'Balafon','correct'=>true],['text'=>'Kora','correct'=>false],['text'=>'Tambour','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le coupé-décalé est dansé sur des rythmes rapides.",'explanation'=>"Vrai. Très énergique.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Qui est l'artiste ivoirien ayant collaboré avec Majid Bekkas ?",'explanation'=>"Manou Gallo (bassiste chanteuse).",'options'=>[['text'=>'Manou Gallo','correct'=>true],['text'=>'Dobet Gnahoré','correct'=>false],['text'=>'Ayelya','correct'=>false],['text'=>'Zézé','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le DJ Arafat était ivoirien.",'explanation'=>"Faux. Ivoirien ? Non, ivoirien ? Il était ivoirien (né à Yopougon).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle chanson a rendu célèbre Meiway ?",'explanation'=>"Pétrole (Zoblazo).",'options'=>[['text'=>'Pétrole','correct'=>true],['text'=>'Miss Lolo','correct'=>false],['text'=>'200% Zoblazo','correct'=>false],['text'=>'Do Ré Mi Fa','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Dobet Gnahoré a gagné un Grammy.",'explanation'=>"Vrai. En 2011 (avec le groupe AfroCubism).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== VILLES & RÉGIONS (10 questions) ====================
            $categories['Villes & Régions'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle ville est surnommée 'cité du Santé' ?",'explanation'=>"Yamoussoukro.",'options'=>[['text'=>'Yamoussoukro','correct'=>true],['text'=>'Abidjan','correct'=>false],['text'=>'Bouaké','correct'=>false],['text'=>'Korhogo','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Daloa est la capitale du cacao.",'explanation'=>"Vrai. Centre cacaoyer majeur.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel district a pour chef-lieu Korhogo ?",'explanation'=>"Savanes.",'options'=>[['text'=>'Savanes','correct'=>true],['text'=>'Denguélé','correct'=>false],['text'=>'Zanzan','correct'=>false],['text'=>'Woroba','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Bouaké est la 2e ville la plus peuplée.",'explanation'=>"Vrai. Après Abidjan.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle région est réputée pour le parc de la Comoé ?",'explanation'=>"Zanzan.",'options'=>[['text'=>'Zanzan','correct'=>true],['text'=>'Savanes','correct'=>false],['text'=>'Lacs','correct'=>false],['text'=>'Denguélé','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Man est la capitale du Tonkpi.",'explanation'=>"Vrai. Région des montagnes.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle ville est connue pour sa cathédrale Saint-Paul ?",'explanation'=>"Abidjan (Plateau).",'options'=>[['text'=>'Abidjan','correct'=>true],['text'=>'Yamoussoukro','correct'=>false],['text'=>'Bouaké','correct'=>false],['text'=>'San-Pédro','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Grand-Bassam a été capitale coloniale.",'explanation'=>"Vrai. De 1893 à 1900.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel est le district le plus peuplé ?",'explanation'=>"District d'Abidjan (plus de 5 M d'habitants).",'options'=>[['text'=>'Abidjan','correct'=>true],['text'=>'Savanes','correct'=>false],['text'=>'Comoé','correct'=>false],['text'=>'Montagnes','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le District du Denguélé a pour chef-lieu Odienné.",'explanation'=>"Vrai. Nord-ouest.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== PATRIMOINE & TOURISME (10 questions) ====================
            $categories['Patrimoine & Tourisme'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quel monument se trouve à Yamoussoukro ?",'explanation'=>"Basilique Notre-Dame de la Paix.",'options'=>[['text'=>'Cathédrale Saint-Paul','correct'=>false],['text'=>'Basilique Notre-Dame','correct'=>true],['text'=>'Mosquée du Plateau','correct'=>false],['text'=>'Palais de la Culture','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Le parc national de la Comoé est réserve UNESCO.",'explanation'=>"Vrai. Réserve de biosphère.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel site balnéaire est près d'Abidjan ?",'explanation'=>"Grand-Bassam (plages historiques).",'options'=>[['text'=>'San-Pédro','correct'=>false],['text'=>'Grand-Bassam','correct'=>true],['text'=>'Jacqueville','correct'=>false],['text'=>'Assinie','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le mont Nimba est plus haut que le mont Tonkoui.",'explanation'=>"Vrai. 1752m vs 1100m.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel parc national est célèbre pour les chimpanzés ?",'explanation'=>"Parc de Taï (primates rares).",'options'=>[['text'=>'Comoé','correct'=>false],['text'=>'Taï','correct'=>true],['text'=>'Mont Nimba','correct'=>false],['text'=>'Marahoué','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Basilique de Yamoussoukro est la plus grande église du monde.",'explanation'=>"Vrai. En superficie (Guinness).",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel musée est installé dans l'ancien palais du gouverneur ?",'explanation'=>"Musée de Grand-Bassam.",'options'=>[['text'=>'Musée des Civilisations','correct'=>false],['text'=>'Musée du Costume','correct'=>false],['text'=>'Musée de Grand-Bassam','correct'=>true],['text'=>'Musée d\'Art Contemporain','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Les cascades de Man sont un site touristique majeur.",'explanation'=>"Vrai. Excursions populaires.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel fleuve est utilisé pour le rafting ?",'explanation'=>"Le Sassandra (à Soubré).",'options'=>[['text'=>'Bandama','correct'=>false],['text'=>'Sassandra','correct'=>true],['text'=>'Comoé','correct'=>false],['text'=>'Cavally','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le Palais de la Culture est à Treichville.",'explanation'=>"Vrai. Lieu d'art et de spectacle.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== ÉDUCATION & SOCIÉTÉ (10 questions) ====================
            $categories['Éducation & Société'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle université publique est à Bouaké ?",'explanation'=>"Université Alassane Ouattara.",'options'=>[['text'=>'Université FHB','correct'=>false],['text'=>'Université Alassane Ouattara','correct'=>true],['text'=>'Université Peleforo Gon Coulibaly','correct'=>false],['text'=>'Université Nangui Abrogoua','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La scolarisation est gratuite jusqu'à 16 ans.",'explanation'=>"Vrai. Officiellement depuis 2015.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel taux d'alphabétisation adulte (15+ ans) ?",'explanation'=>"Environ 57% (2022).",'options'=>[['text'=>'47%','correct'=>false],['text'=>'57%','correct'=>true],['text'=>'67%','correct'=>false],['text'=>'77%','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le système éducatif ivoirien suit le modèle LMD.",'explanation'=>"Vrai. Licence-Master-Doctorat.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle est l'université la plus ancienne ?",'explanation'=>"Université Félix Houphouët-Boigny (1964).",'options'=>[['text'=>'UFHB','correct'=>true],['text'=>'UAO','correct'=>false],['text'=>'INPHB','correct'=>false],['text'=>'ESATIC','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"L'école primaire dure 6 ans.",'explanation'=>"Vrai. CP au CM2.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel pourcentage de jeunes a accès au supérieur ?",'explanation'=>"Environ 12% (taux brut).",'options'=>[['text'=>'8%','correct'=>false],['text'=>'12%','correct'=>true],['text'=>'18%','correct'=>false],['text'=>'25%','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le CEPE est l'examen de fin primaire.",'explanation'=>"Vrai. Certificat d'Études.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le nom du baccalauréat ivoirien ?",'explanation'=>"Baccalauréat (séries C, D, A, G).",'options'=>[['text'=>'Baccalauréat','correct'=>true],['text'=>'BEPC','correct'=>false],['text'=>'CAP','correct'=>false],['text'=>'Brevet','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"L'INSAAC forme aux métiers des arts.",'explanation'=>"Vrai. Institut National des Arts.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== CHRISTIANISME EN CÔTE D'IVOIRE (10 questions) ====================
            $categories['Christianisme en Côte d’Ivoire'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle mission a établi la première école à Grand-Bassam ?",'explanation'=>"Mission méthodiste (1843).",'options'=>[['text'=>'Catholique','correct'=>false],['text'=>'Méthodiste','correct'=>true],['text'=>'Évangélique','correct'=>false],['text'=>'Presbytérienne','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"Noël est un jour férié en Côte d'Ivoire.",'explanation'=>"Vrai. Fête nationale.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quelle fête chrétienne donne lieu à un pèlerinage à N'Gokro ?",'explanation'=>"Pâques (à Yamoussoukro).",'options'=>[['text'=>'Noël','correct'=>false],['text'=>'Pâques','correct'=>true],['text'=>'Assomption','correct'=>false],['text'=>'Toussaint','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La Côte d'Ivoire est majoritairement protestante.",'explanation'=>"Faux. Catholique + Évangéliques divers.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le cardinal ivoirien connu ?",'explanation'=>"Cardinal Bernard Agré (de 2001 à 2014).",'options'=>[['text'=>'Bernard Agré','correct'=>true],['text'=>'Laurent Aké','correct'=>false],['text'=>'Jean-Pierre Kutwa','correct'=>false],['text'=>'Ignace Gboh','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Côte d'Ivoire a deux basiliques.",'explanation'=>"Faux. Une seule basilique majeure.",'options'=>[['text'=>'Vrai','correct'=>false],['text'=>'Faux','correct'=>true]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quel pape a visité la Côte d'Ivoire en 1990 ?",'explanation'=>"Jean-Paul II (à Yamoussoukro).",'options'=>[['text'=>'Jean-Paul II','correct'=>true],['text'=>'Benoît XVI','correct'=>false],['text'=>'François','correct'=>false],['text'=>'Paul VI','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"La fête de l'Assomption est le 15 août.",'explanation'=>"Vrai. Fête catholique.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le pourcentage de chrétiens en CI ?",'explanation'=>"Environ 44% (mixte).",'options'=>[['text'=>'30%','correct'=>false],['text'=>'44%','correct'=>true],['text'=>'55%','correct'=>false],['text'=>'60%','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le Vatican a une nonciature à Abidjan.",'explanation'=>"Vrai. Ambassade du Vatican.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],

            // ==================== ISLAM EN CÔTE D'IVOIRE (10 questions) ====================
            $categories['Islam en Côte d’Ivoire'] => [
                ['type'=>'qcm','difficulty'=>1,'question_text'=>"Quelle est la plus grande mosquée d'Abidjan ?",'explanation'=>"Mosquée du Plateau (Cocody aussi).",'options'=>[['text'=>'Mosquée du Plateau','correct'=>true],['text'=>'Mosquée d\'Adjamé','correct'=>false],['text'=>'Mosquée de Yopougon','correct'=>false],['text'=>'Mosquée d\'Abobo','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La Tabaski est une fête musulmane en Côte d'Ivoire.",'explanation'=>"Vrai. Fériée.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Dans quelle région l'islam est-il le plus répandu ?",'explanation'=>"Nord (Savanes, Denguélé).",'options'=>[['text'=>'Nord','correct'=>true],['text'=>'Sud','correct'=>false],['text'=>'Est','correct'=>false],['text'=>'Ouest','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Les Dioula ont joué un rôle clé dans l'expansion de l'islam.",'explanation'=>"Vrai. Commerçants et missionnaires.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel est le pourcentage de musulmans en Côte d'Ivoire ?",'explanation'=>"Environ 38% (sources variées).",'options'=>[['text'=>'25%','correct'=>false],['text'=>'38%','correct'=>true],['text'=>'45%','correct'=>false],['text'=>'50%','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>1,'question_text'=>"La fin du Ramadan est célébrée (Eid al-Fitr).",'explanation'=>"Vrai. Fériée.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>3,'question_text'=>"Quelle confrérie musulmane est influente au nord ?",'explanation'=>"Tijaniyya (présente en Afrique de l'Ouest).",'options'=>[['text'=>'Qadiriyya','correct'=>false],['text'=>'Tijaniyya','correct'=>true],['text'=>'Sanoussiyya','correct'=>false],['text'=>'Mouridiyya','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Korhogo a une grande mosquée historique.",'explanation'=>"Vrai. Architecture soudanaise.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
                ['type'=>'qcm','difficulty'=>2,'question_text'=>"Quel président ivoirien s'est converti à l'islam ?",'explanation'=>"Alassane Ouattara (musulman de naissance).",'options'=>[['text'=>'Félix Houphouët-Boigny','correct'=>false],['text'=>'Alassane Ouattara','correct'=>true],['text'=>'Henri Konan Bédié','correct'=>false],['text'=>'Laurent Gbagbo','correct'=>false]]],
                ['type'=>'vrai_faux','difficulty'=>2,'question_text'=>"Le vendredi est jour de prière pour les musulmans.",'explanation'=>"Vrai. Pas férié officiellement.",'options'=>[['text'=>'Vrai','correct'=>true],['text'=>'Faux','correct'=>false]]],
            ],
        ];

        foreach ($questionsByCategory as $categoryId => $questions) {
            foreach ($questions as $questionData) {
                $question = Question::updateOrCreate(
                    [
                        'category_id' => $categoryId,
                        'question_text' => $questionData['question_text'],
                    ],
                    [
                        'type' => $questionData['type'],
                        'explanation' => $questionData['explanation'],
                        'difficulty' => $questionData['difficulty'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                foreach ($questionData['options'] as $optionData) {
                    Option::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'option_text' => $optionData['text'],
                        ],
                        [
                            'is_correct' => $optionData['correct'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        $this->command?->info('✅ Questions ajoutées avec succès pour les 14 catégories !');
    }
}