<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Character;
use App\Entity\CharacterRelation;
use App\Entity\Contact;
use App\Entity\Location;
use App\Entity\Note;
use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\ProjectTypeConfig;
use App\Entity\Report;
use App\Entity\ScenarioElement;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\WorldEvent;
use App\Service\ProjectConfigGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures complètes — ScénArt v2
 *
 * Comptes :
 *   superadmin@scenart.dev  / SuperAdmin1234!  → ROLE_SUPER_ADMIN
 *   admin@scenart.dev       / Admin1234!       → ROLE_ADMIN
 *   modo@scenart.dev        / Modo1234!        → ROLE_MODO
 *   thibault@scenart.dev    / Thibault1234!    → ROLE_USER  (3 projets)
 *   alice@scenart.dev       / Alice1234!       → ROLE_USER  (2 projets)
 *   demo@scenart.dev        / Demo1234!        → ROLE_USER  (1 projet)
 *   banned@scenart.dev      / Banned1234!      → ROLE_USER, banni
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ProjectConfigGenerator     $configGenerator,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ══════════════════════════════════════════════════════════════════════
        // 1. UTILISATEURS
        // ══════════════════════════════════════════════════════════════════════

        $superAdmin = $this->makeUser($manager,
            email: 'superadmin@scenart.dev', username: 'superadmin',
            firstName: 'Super', lastName: 'Admin',
            password: 'SuperAdmin1234!',
            roles: ['ROLE_SUPER_ADMIN'],
        );
        $admin = $this->makeUser($manager,
            email: 'admin@scenart.dev', username: 'scenart_admin',
            firstName: 'Admin', lastName: 'ScénArt',
            password: 'Admin1234!',
            roles: ['ROLE_ADMIN'],
        );
        $modo = $this->makeUser($manager,
            email: 'modo@scenart.dev', username: 'modo_scenart',
            firstName: 'Marco', lastName: 'Delacroix',
            password: 'Modo1234!',
            roles: ['ROLE_MODO'],
        );
        $thibault = $this->makeUser($manager,
            email: 'thibault@scenart.dev', username: 'thibault',
            firstName: 'Thibault', lastName: 'Dupont',
            password: 'Thibault1234!',
        );
        $alice = $this->makeUser($manager,
            email: 'alice@scenart.dev', username: 'alice_writes',
            firstName: 'Alice', lastName: 'Martin',
            password: 'Alice1234!',
        );
        $demo = $this->makeUser($manager,
            email: 'demo@scenart.dev', username: 'demo_user',
            firstName: 'Demo', lastName: 'User',
            password: 'Demo1234!',
            locale: 'en',
        );
        $banned = $this->makeUser($manager,
            email: 'banned@scenart.dev', username: 'banned_user',
            firstName: 'Bob', lastName: 'Interdit',
            password: 'Banned1234!',
            isBanned: true,
        );

        // ══════════════════════════════════════════════════════════════════════
        // UTILISATEURS SUPPLÉMENTAIRES (×30)
        // ══════════════════════════════════════════════════════════════════════

        $extraUsers = [];
        $extraUsers[] = $this->makeUser($manager, email: 'emma.rousseau@scenart.dev',   username: 'emma_rousseau',   firstName: 'Emma',      lastName: 'Rousseau',    password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'luc.moreau@scenart.dev',       username: 'luc_moreau',      firstName: 'Luc',       lastName: 'Moreau',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'chloe.bernard@scenart.dev',    username: 'chloe_b',         firstName: 'Chloé',     lastName: 'Bernard',     password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'mathieu.lefebvre@scenart.dev', username: 'mathieu_lf',      firstName: 'Mathieu',   lastName: 'Lefebvre',    password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'sarah.petit@scenart.dev',      username: 'sarah_petit',     firstName: 'Sarah',     lastName: 'Petit',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'nicolas.henry@scenart.dev',    username: 'nico_henry',      firstName: 'Nicolas',   lastName: 'Henry',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'julie.thomas@scenart.dev',     username: 'julie_thomas',    firstName: 'Julie',     lastName: 'Thomas',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'pierre.garcia@scenart.dev',    username: 'pierre_garcia',   firstName: 'Pierre',    lastName: 'Garcia',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'laura.martinez@scenart.dev',   username: 'laura_mtz',       firstName: 'Laura',     lastName: 'Martinez',    password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'kevin.simon@scenart.dev',      username: 'kevin_simon',     firstName: 'Kevin',     lastName: 'Simon',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'amelia.dupont@scenart.dev',    username: 'amelia_dp',       firstName: 'Amélia',    lastName: 'Dupont',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'romain.leclerc@scenart.dev',   username: 'romain_lc',       firstName: 'Romain',    lastName: 'Leclerc',     password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'clara.fontaine@scenart.dev',   username: 'clara_font',      firstName: 'Clara',     lastName: 'Fontaine',    password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'hugo.lambert@scenart.dev',     username: 'hugo_lambert',    firstName: 'Hugo',      lastName: 'Lambert',     password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'marie.bonnet@scenart.dev',     username: 'marie_bonnet',    firstName: 'Marie',     lastName: 'Bonnet',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'ethan.girard@scenart.dev',     username: 'ethan_girard',    firstName: 'Ethan',     lastName: 'Girard',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'zoe.chevalier@scenart.dev',    username: 'zoe_chev',        firstName: 'Zoé',       lastName: 'Chevalier',   password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'bastien.morel@scenart.dev',    username: 'bastien_morel',   firstName: 'Bastien',   lastName: 'Morel',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'ines.colin@scenart.dev',       username: 'ines_colin',      firstName: 'Inès',      lastName: 'Colin',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'florian.david@scenart.dev',    username: 'florian_dv',      firstName: 'Florian',   lastName: 'David',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'camille.robert@scenart.dev',   username: 'camille_rb',      firstName: 'Camille',   lastName: 'Robert',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'leo.richard@scenart.dev',      username: 'leo_richard',     firstName: 'Léo',       lastName: 'Richard',     password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'noemie.durand@scenart.dev',    username: 'noemie_dr',       firstName: 'Noémie',    lastName: 'Durand',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'alexis.perrin@scenart.dev',    username: 'alexis_perrin',   firstName: 'Alexis',    lastName: 'Perrin',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'manon.blanc@scenart.dev',      username: 'manon_blanc',     firstName: 'Manon',     lastName: 'Blanc',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'theo.vidal@scenart.dev',       username: 'theo_vidal',      firstName: 'Théo',      lastName: 'Vidal',       password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'pauline.lebrun@scenart.dev',   username: 'pauline_lb',      firstName: 'Pauline',   lastName: 'Lebrun',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'arthur.michel@scenart.dev',    username: 'arthur_michel',   firstName: 'Arthur',    lastName: 'Michel',      password: 'User1234!');
        $extraUsers[] = $this->makeUser($manager, email: 'lucie.arnaud@scenart.dev',     username: 'lucie_arnaud',    firstName: 'Lucie',     lastName: 'Arnaud',      password: 'User1234!', locale: 'en');
        $extraUsers[] = $this->makeUser($manager, email: 'nathan.dupuis@scenart.dev',    username: 'nathan_dupuis',   firstName: 'Nathan',    lastName: 'Dupuis',      password: 'User1234!');

        // ══════════════════════════════════════════════════════════════════════
        // 2. PROJET FILM — Les Ombres de Valoria  (thibault, public, in_progress)
        // ══════════════════════════════════════════════════════════════════════

        $film = $this->makeProject($manager,
            owner: $thibault,
            title: 'Les Ombres de Valoria',
            description: 'Un thriller psychologique dans une ville corrompue où chaque vérité cache un mensonge. '
                . 'Sara Kane, détective brillante, rouvre l\'affaire qui a coûté la vie à son partenaire — '
                . 'et découvre que la corruption va plus loin qu\'elle ne l\'imaginait.',
            type: 'film',
            status: 'in_progress',
            visibility: Project::VISIBILITY_PUBLIC,
            moderationStatus: 'approved',
        );
        $manager->flush();
        $this->configGenerator->generateConfigsForDepth($film, 'film', 3);
        $manager->flush();

        // Tags
        $tagAction = $this->makeTag($manager, $film, 'Action',       '#EF4444');
        $tagCle    = $this->makeTag($manager, $film, 'Clé du récit', '#10B981');
        $tagFlash  = $this->makeTag($manager, $film, 'Flashback',    '#6366F1');
        $tagTwist  = $this->makeTag($manager, $film, 'Twist',        '#F59E0B');

        // Lieux
        $valoria  = $this->makeLocation($manager, $film, 'Valoria',          'La ville maudite dominée par le réseau Drell.',                    'exterior', null);
        $pont     = $this->makeLocation($manager, $film, 'Le Pont des Âmes', 'Lieu de la première découverte. Symbol clé du film.',              'exterior', $valoria);
        $entrepot = $this->makeLocation($manager, $film, 'Entrepôt Harkon',  'Repaire du réseau criminel de Drell, sous les quais.',             'interior', $valoria);
        $commis   = $this->makeLocation($manager, $film, 'Commissariat 9e',  'QG de Sara. Murs couverts de photos, dossiers partout.',           'interior', null);
        $manoir   = $this->makeLocation($manager, $film, 'Manoir Drell',     'Résidence de Marcus Drell — façade respectable, arrière-cour lugubre.','interior', $valoria);

        // Personnages
        $sara = $this->makeCharacter($manager, $film,
            name: 'Sara Kane',
            firstName: 'Sara', lastName: 'Kane',
            role: 'protagonist',
            description: 'Détective brillante, hantée par un passé trouble.',
            biography: 'Née à Valoria, Sara Kane a intégré la police criminelle à 24 ans après des études en criminologie à Bruxelles. Sa rigueur et son instinct lui ont valu une réputation solide, mais aussi de nombreux ennemis au sein du système.',
            goals: 'Découvrir qui se cache derrière le réseau de corruption qui gangrène Valoria.',
            motivations: 'La mort non élucidée de son partenaire il y a trois ans.',
        );
        $marcus = $this->makeCharacter($manager, $film,
            name: 'Marcus Drell',
            firstName: 'Marcus', lastName: 'Drell',
            role: 'antagonist',
            description: 'L\'homme de l\'ombre, architecte de la corruption de Valoria.',
            biography: 'Issu de la haute bourgeoisie valorienne, Marcus Drell a construit un empire en manipulant la justice et la politique locale pendant deux décennies. Respecté en surface, redouté en coulisses.',
            goals: 'Maintenir son pouvoir absolu sur Valoria à tout prix.',
            motivations: 'La peur de voir son passé révélé : un meurtre commandité à l\'âge de 30 ans.',
        );
        $lynne = $this->makeCharacter($manager, $film,
            name: 'Lynne Ramos',
            firstName: 'Lynne', lastName: 'Ramos',
            role: 'secondary',
            description: 'Partenaire et confidente de Sara. Pied dans deux camps.',
            biography: 'Inspectrice expérimentée, Lynne est le pilier moral de l\'équipe. Elle doute des méthodes de Sara mais lui fait une confiance aveugle. Ce que Sara ignore : Lynne a couvert le meurtre du partenaire il y a trois ans.',
            goals: 'Protéger Sara tout en gardant son secret.',
            motivations: 'L\'amitié, la culpabilité, et la peur de tout perdre.',
        );
        $reeves = $this->makeCharacter($manager, $film,
            name: 'Capitaine Reeves',
            firstName: 'Henri', lastName: 'Reeves',
            role: 'secondary',
            description: 'Supérieur hiérarchique de Sara. Neutralité suspecte.',
            biography: 'Chef de la brigade criminelle du 9e. Vingt-deux ans de service. Reeves est le symbole d\'un système à bout de souffle — ou complice actif. Sara ne sait pas encore lequel.',
            goals: 'Maintenir l\'ordre apparent du commissariat.',
            motivations: 'Sa retraite dans deux ans. Ne pas faire de vagues.',
        );

        $this->makeRelation($manager, $sara,   $marcus, 'enemy',   'Opposition directe : Sara enquête sur les crimes orchestrés par Drell.', true);
        $this->makeRelation($manager, $sara,   $lynne,  'ally',    'Partenaires de travail — amitié profonde malgré les désaccords de méthode.', true);
        $this->makeRelation($manager, $marcus, $lynne,  'unknown', 'Drell sait que Lynne a couvert le meurtre — levier de pression.', false);
        $this->makeRelation($manager, $sara,   $reeves, 'rival',   'Tension hiérarchique : Sara court-circuite Reeves, il la surveille.', true);

        // Structure narrative (Acte → Séquence → Scène)
        $acte1 = $this->makeElement($manager, $film, null, 'act', 1, 'Acte I — L\'Éveil', 'Découverte du corps sur le pont. Sara reprend l\'enquête.', 1);
            $seq1 = $this->makeElement($manager, $film, $acte1, 'sequence', 2, 'Séquence 1 — La scène de crime', 'L\'équipe découvre le corps. Premiers indices.', 1);
                $this->makeElement($manager, $film, $seq1, 'scene', 3, 'Scène 1 — Le Pont des Âmes', 'Sara observe la scène. Lynne documente. Le corps est celui d\'un comptable du réseau Drell.', 1, [$tagAction, $tagCle], [
                    ['type' => 'slug',   'content' => 'EXT. LE PONT DES ÂMES — NUIT'],
                    ['type' => 'action', 'content' => 'Pluie fine. Les néons de la ville se reflètent dans la Seine noire. Des rubans de police claquent dans le vent.'],
                    ['type' => 'action', 'content' => 'SARA KANE (38 ans, imperméable sombre, regard acéré) s\'avance vers la berge. Derrière elle, LYNNE RAMOS documente la scène avec son téléphone.'],
                    ['type' => 'char',   'content' => 'LYNNE'],
                    ['type' => 'diag',   'content' => 'Homme, la cinquantaine. Portefeuille dans la poche intérieure. Julien Morel. Comptable indépendant.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Un comptable qui travaillait pour qui ?'],
                    ['type' => 'char',   'content' => 'LYNNE'],
                    ['type' => 'parenthetical', 'content' => '(hésitation)'],
                    ['type' => 'diag',   'content' => 'On vérifie. Mais son téléphone... il a reçu un message ce soir. Expéditeur inconnu. \"Arrête de fouiller ou ta famille paie.\"'],
                    ['type' => 'action', 'content' => 'Sara regarde le pont. Ce pont. Elle connaît ce pont.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'parenthetical', 'content' => '(voix basse)'],
                    ['type' => 'diag',   'content' => 'Trois ans. C\'est toujours le même pont.'],
                ]);
                $this->makeElement($manager, $film, $seq1, 'scene', 3, 'Scène 2 — Le Commissariat', 'Sara confronte Reeves sur le manque de ressources. Il lui rappelle ses limites.', 2, [$tagCle], [
                    ['type' => 'slug',   'content' => 'INT. COMMISSARIAT DU 9E — BUREAU DE REEVES — JOUR'],
                    ['type' => 'action', 'content' => 'Lumière blanche. Piles de dossiers. CAPITAINE REEVES (58 ans, costume gris, fatigue institutionnelle dans chaque ride) ne lève pas les yeux de son écran.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Je veux deux techniciens supplémentaires et l\'accès aux archives de 2021. Affaire Morel.'],
                    ['type' => 'char',   'content' => 'REEVES'],
                    ['type' => 'parenthetical', 'content' => '(sans lever les yeux)'],
                    ['type' => 'diag',   'content' => 'Les archives de 2021 sont classifiées niveau deux. Vous n\'avez pas l\'habilitation.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Morel a reçu une menace de mort. Quelqu\'un sait qu\'il fouillait. Fouillait quoi, capitaine ?'],
                    ['type' => 'action', 'content' => 'Reeves pose enfin son stylo. Leurs regards se croisent. Un silence chargé.'],
                    ['type' => 'char',   'content' => 'REEVES'],
                    ['type' => 'diag',   'content' => 'Inspecteur Kane. Je vous rappelle que vous êtes à deux ans de votre retraite anticipée. Ne la gâchez pas.'],
                    ['type' => 'transition', 'content' => 'COUPE SUR :'],
                ]);
            $seq2 = $this->makeElement($manager, $film, $acte1, 'sequence', 2, 'Séquence 2 — Les premières pistes', 'Sara tire le premier fil.', 2);
                $this->makeElement($manager, $film, $seq2, 'scene', 3, 'Scène 3 — L\'archive', 'Sara retrouve une mention de Marcus Drell dans un vieux dossier classé.', 1, [$tagCle], [
                    ['type' => 'slug',   'content' => 'INT. SALLE D\'ARCHIVES — SOUS-SOL DU COMMISSARIAT — NUIT'],
                    ['type' => 'action', 'content' => 'Sara est seule. Une lampe de bureau. Des cartons entassés. Elle feuillette un dossier jauni : «\u{00a0}Réseau Harkon — 2018\u{00a0}».'],
                    ['type' => 'action', 'content' => 'Elle s\'arrête. Un nom. Elle le relit deux fois.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'parenthetical', 'content' => '(pour elle-même)'],
                    ['type' => 'diag',   'content' => 'Marcus Drell...'],
                    ['type' => 'action', 'content' => 'Elle sort son téléphone. Cherche. Des photos : soirées de gala, poignées de main officielles, sourire impeccable.'],
                    ['type' => 'action', 'content' => 'Sara referme le dossier. Elle n\'a pas l\'habilitation. Elle s\'en fout.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Bonne nuit, monsieur Drell.'],
                ]);

        $acte2 = $this->makeElement($manager, $film, null, 'act', 1, 'Acte II — Les Mensonges', 'L\'enquête se complexifie. Le passé de Sara remonte à la surface.', 2);
            $seq3 = $this->makeElement($manager, $film, $acte2, 'sequence', 2, 'Séquence 3 — Le passé refait surface', 'Un flashback révèle la mort du partenaire de Sara.', 1);
                $this->makeElement($manager, $film, $seq3, 'scene', 3, 'Scène 4 — Flashback : Trois ans plus tôt', 'Même lieu. Le partenaire de Sara est abattu. Dans l\'ombre : la silhouette de Lynne.', 1, [$tagFlash, $tagCle], [
                    ['type' => 'slug',   'content' => 'EXT. LE PONT DES ÂMES — NUIT  — IL Y A TROIS ANS'],
                    ['type' => 'action', 'content' => 'Image légèrement désaturée. Même pont. Même pluie. Autre époque.'],
                    ['type' => 'action', 'content' => 'Sara (35 ans, plus jeune, plus vulnérable) court sur le pont. Elle tient un dossier sous la pluie.'],
                    ['type' => 'action', 'content' => 'Un coup de feu. Son partenaire, THOMAS VEGA (42 ans), s\'effondre.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Thomas — Thomas !'],
                    ['type' => 'action', 'content' => 'Dans l\'ombre, au bout du pont : une silhouette. Immobile. Elle regarde. Elle attend.'],
                    ['type' => 'action', 'content' => 'La silhouette tourne les talons. Sara ne peut pas la voir clairement — mais nous, nous reconnaissons la démarche de LYNNE.'],
                    ['type' => 'transition', 'content' => 'RETOUR AU PRÉSENT'],
                ]);
                $this->makeElement($manager, $film, $seq3, 'scene', 3, 'Scène 5 — L\'Entrepôt Harkon', 'Sara infiltre le repaire. Elle découvre des preuves compromettantes pour Drell... et pour Lynne.', 2, [$tagAction], [
                    ['type' => 'slug',   'content' => 'INT. ENTREPÔT HARKON — QUAIS SUD — NUIT'],
                    ['type' => 'action', 'content' => 'Sara progresse dans l\'obscurité. Elle porte une oreillette. Pas de backup — elle n\'a pas voulu en demander.'],
                    ['type' => 'action', 'content' => 'Un bureau improvisé. Des écrans. Des transferts financiers. Des noms qu\'elle reconnaît : élus, juges, commissaires.'],
                    ['type' => 'action', 'content' => 'Et une photo. Lynne. Thomas. Le pont. Trois ans plus tôt.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'parenthetical', 'content' => '(voix brisée)'],
                    ['type' => 'diag',   'content' => 'Non.'],
                    ['type' => 'action', 'content' => 'Elle sort la photo. La glisse dans sa poche. Elle ne tremble pas. Elle respire fort. C\'est tout.'],
                ]);
            $seq4 = $this->makeElement($manager, $film, $acte2, 'sequence', 2, 'Séquence 4 — La confrontation', 'Sara et Lynne s\'affrontent.', 2);
                $this->makeElement($manager, $film, $seq4, 'scene', 3, 'Scène 6 — La vérité sur Lynne', 'Sara confronte Lynne. Silence lourd. Lynne admet avoir couvert le meurtre pour protéger une source.', 1, [$tagTwist, $tagCle], [
                    ['type' => 'slug',   'content' => 'INT. COMMISSARIAT DU 9E — SALLE D\'INTERROGATOIRE — NUIT'],
                    ['type' => 'action', 'content' => 'Deux chaises. Une table. Sara pose la photo entre elles.'],
                    ['type' => 'action', 'content' => 'LYNNE regarde la photo. Longtemps. Trop longtemps.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Tu étais là.'],
                    ['type' => 'action', 'content' => 'Silence.'],
                    ['type' => 'char',   'content' => 'LYNNE'],
                    ['type' => 'parenthetical', 'content' => '(voix basse, sans se défendre)'],
                    ['type' => 'diag',   'content' => 'J\'avais une source dans le réseau Drell. Thomas allait la brûler. Si elle mourait, Drell n\'était plus jamais touché.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Thomas est mort.'],
                    ['type' => 'char',   'content' => 'LYNNE'],
                    ['type' => 'diag',   'content' => 'Je sais. Je vis avec tous les jours.'],
                    ['type' => 'action', 'content' => 'Sara se lève. Elle ne pleure pas. Elle n\'a plus de larmes pour ça.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Va-t-en. Si tu es encore là dans dix minutes, je t\'arrête.'],
                ]);

        $acte3 = $this->makeElement($manager, $film, null, 'act', 1, 'Acte III — Le Dénouement', 'Sara doit choisir entre la justice et l\'amitié. Le réseau s\'effondre.', 3);
            $seq5 = $this->makeElement($manager, $film, $acte3, 'sequence', 2, 'Séquence 5 — La chute de Drell', 'Dénouement final.', 1);
                $this->makeElement($manager, $film, $seq5, 'scene', 3, 'Scène 7 — Le Manoir Drell', 'Sara arrête Marcus Drell. Il tente de marchander avec les preuves contre Lynne.', 1, [$tagAction, $tagTwist], [
                    ['type' => 'slug',   'content' => 'INT. MANOIR DRELL — SALON PRINCIPAL — NUIT'],
                    ['type' => 'action', 'content' => 'Marcus Drell (65 ans, robe de chambre bordeaux, cognac à la main) n\'a pas l\'air surpris quand Sara entre par la fenêtre.'],
                    ['type' => 'char',   'content' => 'DRELL'],
                    ['type' => 'diag',   'content' => 'Inspecteur Kane. Je vous attendais. Pas ce soir, mais... tôt ou tard.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Marcus Drell, vous êtes en état d\'arrestation pour...'],
                    ['type' => 'char',   'content' => 'DRELL'],
                    ['type' => 'parenthetical', 'content' => '(l\'interrompant)'],
                    ['type' => 'diag',   'content' => 'Avant les menottes — un mot. J\'ai les enregistrements complets de la nuit du pont. Votre amie Ramos. En haute définition.'],
                    ['type' => 'action', 'content' => 'Sara ne bouge pas. Ne trahit rien.'],
                    ['type' => 'char',   'content' => 'SARA'],
                    ['type' => 'diag',   'content' => 'Monsieur Drell. Vous avez le droit de garder le silence.'],
                    ['type' => 'action', 'content' => 'Elle sort les menottes. Drell, pour la première fois de sa vie, comprend que ça ne marchera pas.'],
                ]);
                $this->makeElement($manager, $film, $seq5, 'scene', 3, 'Scène 8 — Épilogue', 'Sara revient seule sur le pont. Elle dépose les fleurs de son partenaire. Plan final.', 2, [$tagCle], [
                    ['type' => 'slug',   'content' => 'EXT. LE PONT DES ÂMES — AUBE'],
                    ['type' => 'action', 'content' => 'Lumière rose pâle. Le fleuve est calme pour une fois. Sara est seule sur le pont.'],
                    ['type' => 'action', 'content' => 'Elle pose un bouquet de fleurs blanches sur le rebord. Les fleurs de Thomas. Rituels des vivants pour leurs morts.'],
                    ['type' => 'action', 'content' => 'Son téléphone vibre. Elle regarde l\'écran : un SMS de Lynne. Deux mots : «\u{00a0}Pardon. Merci.\u{00a0}»'],
                    ['type' => 'action', 'content' => 'Sara éteint son téléphone. Elle regarde le fleuve.'],
                    ['type' => 'action', 'content' => 'LONG PLAN FIXE sur son visage. Pas de larmes. Juste quelqu\'un qui apprend à continuer.'],
                    ['type' => 'transition', 'content' => 'FONDU AU NOIR.'],
                ]);

        // Marquer les éléments du film comme publics (projet en ligne)
        foreach ($film->getScenarioElements() as $el) {
            $el->isPublic = true;
        }
        $manager->flush();

        // Timeline chronologique
        $this->makeWorldEvent($manager, $film, 'Meurtre du partenaire de Sara',     -3, 'L\'événement déclencheur de toute l\'histoire. Lynne était présente.', 11, 14, $pont);
        $this->makeWorldEvent($manager, $film, 'Drell rachète l\'entrepôt Harkon',  -2, 'Couverture légale pour les activités du réseau criminel.', 3, null, $entrepot);
        $this->makeWorldEvent($manager, $film, 'Sara prend le poste au 9e',         -1, 'Mutation suite à l\'enquête non résolue de son partenaire.', 9, null, $commis);
        $this->makeWorldEvent($manager, $film, 'Début de l\'enquête — Jour 1',       0, 'Corps découvert sur le Pont des Âmes. L\'histoire recommence.', 3, 1, $pont);
        $this->makeWorldEvent($manager, $film, 'Infiltration de l\'entrepôt Harkon', 0, 'Sara trouve les preuves — et comprend le rôle de Lynne.', 3, 18, $entrepot);
        $this->makeWorldEvent($manager, $film, 'Arrestation de Marcus Drell',        0, 'Climax. Sara fait son choix. Lynne disparaît dans la nuit.', 3, 22, $manoir);

        // Tâches
        $this->makeTask($manager, $film, $thibault, $thibault,
            title: 'Réviser le dialogue — Scène 6 (confrontation Sara/Lynne)',
            description: 'Le twist de Lynne doit être amené plus subtilement. Retravailler les répliques pour que le spectateur doute jusqu\'au bout.',
            status: 'in_progress', priority: 'high',
            dueDate: new \DateTimeImmutable('+5 days'),
        );
        $this->makeTask($manager, $film, $thibault, $demo,
            title: 'Vérifier la cohérence des dates du flashback',
            description: 'S\'assurer que la timeline du flashback (−3 ans) ne contredit pas les événements de l\'Acte I.',
            status: 'todo', priority: 'normal',
        );
        $this->makeTask($manager, $film, $thibault, null,
            title: 'Descriptions décors — Acte III',
            description: 'Les directions artistiques du manoir Drell et du plan final sont trop légères.',
            status: 'todo', priority: 'low',
        );
        $this->makeTask($manager, $film, $thibault, $alice,
            title: 'Relecture complète Acte II',
            description: 'Vérifier la cohérence du rythme et des motivations de chaque personnage.',
            status: 'review', priority: 'normal',
        );

        // Notes
        $this->makeNote($manager, $film, $thibault,
            title: 'Twist final — version alternative',
            content: 'Et si Reeves était aussi dans le réseau Drell ? Cela permettrait une double trahison en Acte III et renforcerait le sentiment d\'isolement de Sara. À explorer avant le verrouillage du script.',
            status: 'note', priority: 'high',
        );
        $this->makeNote($manager, $film, $thibault,
            title: 'TODO — Scènes nocturnes',
            content: 'Vérifier l\'atmosphère dans toutes les scènes de nuit : le tone doit rester oppressant sans devenir répétitif. Varier les décors mais garder la lumière froide.',
            status: 'todo', priority: 'normal',
        );
        $this->makeNote($manager, $film, $alice,
            title: 'Note de relecture — personnage Lynne',
            content: 'La progression de Lynne dans l\'Acte II est un peu brusque. Le lecteur/spectateur a besoin d\'un signe avant-coureur dès l\'Acte I pour que le twist soit satisfaisant. Suggère : une hésitation de Lynne à la scène du pont (Scène 1).',
            status: 'note', priority: 'normal',
        );

        $this->makeMember($manager, $film, $demo,  'contributor');
        $this->makeMember($manager, $film, $alice, 'editor');

        // ══════════════════════════════════════════════════════════════════════
        // 3. PROJET JEU VIDÉO — Fragments d'Éternité (thibault, privé, draft)
        // ══════════════════════════════════════════════════════════════════════

        $game = $this->makeProject($manager,
            owner: $thibault,
            title: "Fragments d'Éternité",
            description: 'RPG narratif post-apocalyptique. Explorez les ruines d\'une civilisation engloutie par la Rupture — et reconstituez le passé fragment par fragment.',
            type: 'jeu_video',
            status: 'draft',
            visibility: Project::VISIBILITY_UNPUBLISHED,
            moderationStatus: 'clear',
        );
        $manager->flush();
        $this->configGenerator->generateConfigsForDepth($game, 'jeu_video', 3);
        $manager->flush();

        $tagLore  = $this->makeTag($manager, $game, 'Lore',    '#8B5CF6');
        $tagQuete = $this->makeTag($manager, $game, 'Quête',   '#F59E0B');
        $tagBoss  = $this->makeTag($manager, $game, 'Boss',    '#EF4444');
        $tagMemo  = $this->makeTag($manager, $game, 'Mémoire', '#06B6D4');

        $ruines    = $this->makeLocation($manager, $game, 'Ruines d\'Aethoria',  'La capitale déchue. Bâtiments effondrés, végétation envahissante.',          'exterior', null);
        $citadelle = $this->makeLocation($manager, $game, 'Citadelle Brisée',    'Le cœur de la résistance. Dernier bastion de l\'ordre ancien.',              'interior', $ruines);
        $crypte    = $this->makeLocation($manager, $game, 'Crypte des Anciens',  'Lieu de découverte des Fragments. Pleine de pièges et de mémoires.',         'interior', $ruines);
        $sanctuaire= $this->makeLocation($manager, $game, 'Sanctuaire de Sel',   'Prison dorée de l\'Oracle. Accessible uniquement après 3 Fragments.',        'interior', null);

        $kael = $this->makeCharacter($manager, $game,
            name: 'Kaël',
            firstName: 'Kaël', lastName: '',
            role: 'protagonist',
            description: 'Dernier Gardien des Fragments. Amnésique.',
            biography: 'Kaël s\'est réveillé dans les ruines d\'Aethoria sans aucun souvenir. Seule la marque sur son avant-bras — le Sceau des Gardiens — lui indique son rôle dans l\'ordre ancien. Chaque Fragment récupéré lui rend une tranche de mémoire.',
            goals: 'Retrouver les cinq Fragments avant que les Dévoreurs ne les réunissent.',
            motivations: 'Comprendre son passé, honorer le serment des Gardiens et empêcher la Seconde Rupture.',
        );
        $oracle = $this->makeCharacter($manager, $game,
            name: 'L\'Oracle de Sel',
            firstName: 'Oracle', lastName: 'de Sel',
            role: 'secondary',
            description: 'Guide mystérieux emprisonné. Peut-être un ennemi en attente.',
            biography: 'Entité ancienne scellée dans une sphère de cristal depuis la Première Rupture. Connaît l\'emplacement de chaque Fragment mais refuse de révéler ses véritables intentions. Son aide a toujours un prix.',
            goals: 'Guider Kaël vers les Fragments. Être libéré.',
            motivations: 'Sa propre libération, liée à la réunion des cinq Fragments.',
        );
        $devoreuse = $this->makeCharacter($manager, $game,
            name: 'La Dévoreuse',
            firstName: 'Érys', lastName: '',
            role: 'antagonist',
            description: 'Gardienne corrompue par la Rupture. Chasseuse de Fragments.',
            biography: 'Autrefois Gardienne comme Kaël, Érys a été corrompue lors de la Première Rupture. Elle croit que réunir les Fragments déclenchera la Seconde — et souhaite cette apocalypse pour "purifier" le monde.',
            goals: 'Récupérer tous les Fragments avant Kaël et déclencher la Seconde Rupture.',
            motivations: 'Une idéologie apocalyptique et la douleur d\'une perte ancienne.',
        );
        $this->makeRelation($manager, $kael, $oracle,    'unknown', 'Relation ambiguë : guide ou manipulateur ?', false);
        $this->makeRelation($manager, $kael, $devoreuse, 'enemy',   'Miroir inversé : même passé, choix opposés.', true);
        $this->makeRelation($manager, $oracle, $devoreuse,'unknown','L\'Oracle craint la Dévoreuse. Il en sait plus qu\'il ne dit.', false);

        $chap1 = $this->makeElement($manager, $game, null, 'chapter', 1, 'Chapitre 1 — Le Réveil', 'Kaël s\'éveille dans les ruines. Tutoriel narratif.', 1);
            $niv1 = $this->makeElement($manager, $game, $chap1, 'level', 2, 'Niveau 1-1 — Ruines Extérieures', 'Exploration des faubourgs en ruine. Premiers combats.',  1);
                $this->makeElement($manager, $game, $niv1, 'scene', 3, 'Zone A — La Place du Souvenir', "Zone tutoriel. Premiers combats. Kaël découvre le Sceau sur son bras.", 1, [$tagLore, $tagMemo]);
                $this->makeElement($manager, $game, $niv1, 'scene', 3, 'Zone B — Le Marché Fantôme',   "Rencontre avec des survivants. Kaël apprend ce qu'est devenu le monde.", 2, [$tagLore]);
            $niv2 = $this->makeElement($manager, $game, $chap1, 'level', 2, 'Niveau 1-2 — Crypte des Anciens', 'Donjon principal. Premier Fragment au fond.', 2);
                $this->makeElement($manager, $game, $niv2, 'scene', 3, 'Zone A — L\'Antichambre',       "Énigmes environnementales. Fragments de mémoire #1 sur les Gardiens.", 1, [$tagLore, $tagMemo]);
                $this->makeElement($manager, $game, $niv2, 'scene', 3, 'Zone B — La Salle des Anciens', "Boss : Gardien corrompu. Récompense : Fragment de Feu.", 2, [$tagBoss, $tagQuete]);

        $chap2 = $this->makeElement($manager, $game, null, 'chapter', 1, 'Chapitre 2 — La Vérité de Sel', 'Kaël trouve le Sanctuaire. L\'Oracle lui parle pour la première fois.', 2);
            $niv3 = $this->makeElement($manager, $game, $chap2, 'level', 2, 'Niveau 2-1 — Les Plaines de Cendre', 'Zone de transition. Rencontre avec la Dévoreuse.', 1);
                $this->makeElement($manager, $game, $niv3, 'scene', 3, 'Zone A — La Rencontre', "Cinématique : première confrontation avec Érys. Elle connaît Kaël d'avant.", 1, [$tagLore, $tagMemo]);

        // Timeline jeu
        $this->makeWorldEvent($manager, $game, 'La Première Rupture',         -500, 'Catastrophe qui a détruit Aethoria. Les Gardiens ont sacrifié leur mémoire pour sceller les Fragments.', null, null, $ruines);
        $this->makeWorldEvent($manager, $game, 'L\'Oracle emprisonné',        -499, 'Immédiatement après la Rupture. L\'Oracle est enfermé pour avoir tenté de réunir les Fragments seul.',  null, null, $sanctuaire);
        $this->makeWorldEvent($manager, $game, 'Érys corrompue',              -498, 'La future Dévoreuse tombe lors de la purification des Fragments. Sa mémoire est préservée — et torturée.', null, null, $crypte);
        $this->makeWorldEvent($manager, $game, 'Le Réveil de Kaël (Jour 1)',   0,   'Kaël se réveille sans mémoire dans les ruines. L\'histoire commence.', 1, 1, $ruines);
        $this->makeWorldEvent($manager, $game, 'Premier Fragment récupéré',    0,   'Fragment de Feu découvert dans la Crypte. Première mémoire restaurée.', 1, 3, $crypte);

        // Tâches
        $this->makeTask($manager, $game, $thibault, $thibault,
            title: 'Design des capacités — Fragment de Feu',
            description: 'Chaque Fragment donne une capacité unique. Fragment de Feu : bouclier d\'incendie + attaque en cône. Équilibrer avec les zones du Ch.2.',
            status: 'todo', priority: 'high',
        );
        $this->makeTask($manager, $game, $thibault, null,
            title: 'Écrire les 5 fragments de mémoire du Ch.1',
            description: 'Chaque fragment de mémoire doit en apprendre plus sur le rôle de Kaël avant la Rupture, sans tout révéler.',
            status: 'in_progress', priority: 'normal',
        );

        $this->makeNote($manager, $game, $thibault,
            title: 'Concept — Système de mémoire',
            content: 'Chaque Fragment récupéré débloque un souvenir de Kaël sous forme de cinématique ou dialogue. Les 5 mémoires réunies révèlent que Kaël était le chef des Gardiens — et qu\'il a lui-même causé la Rupture pour tenter de sauver Érys.',
            status: 'note', priority: 'urgent',
        );
        $this->makeNote($manager, $game, $thibault,
            title: 'Note — Fin alternative',
            content: 'Si le joueur a trouvé tous les fragments de mémoire, une fin alternative est débloquée : Kaël peut sacrifier les Fragments pour libérer Érys de la corruption plutôt que de déclencher la Rupture. Fin "réconciliation".',
            status: 'note', priority: 'high',
        );

        // ══════════════════════════════════════════════════════════════════════
        // 4. PROJET SÉRIE — Éclipse  (alice, public, in_progress)
        // ══════════════════════════════════════════════════════════════════════

        $serie = $this->makeProject($manager,
            owner: $alice,
            title: 'Éclipse',
            description: 'Série dramatique sur une famille déchirée par un secret vieux de trente ans. '
                . 'Claire Moreau revient dans sa ville natale après la mort de son père — '
                . 'et commence à déterrer ce qui aurait dû rester enfoui.',
            type: 'serie',
            status: 'in_progress',
            visibility: Project::VISIBILITY_PUBLIC,
            moderationStatus: 'clear',
        );
        $manager->flush();
        $this->configGenerator->generateConfigsForDepth($serie, 'serie', 3);
        $manager->flush();

        $tagDrame  = $this->makeTag($manager, $serie, 'Drame',  '#F97316');
        $tagSecret = $this->makeTag($manager, $serie, 'Secret', '#7C3AED');
        $tagFamily = $this->makeTag($manager, $serie, 'Famille','#10B981');

        $maisonMoreau = $this->makeLocation($manager, $serie, 'La Maison Moreau', 'Demeure familiale victorienne. Chaque pièce cache quelque chose.',     'interior', null);
        $marent       = $this->makeLocation($manager, $serie, 'Ville de Marent',  'Ville provinciale où tout le monde se connaît — et tout se sait.',     'exterior', null);
        $notaire      = $this->makeLocation($manager, $serie, 'Étude Bergeron',   'Bureau du notaire. Lieu de la révélation du testament.',               'interior', $marent);
        $lac          = $this->makeLocation($manager, $serie, 'Lac des Hêtres',   'En périphérie de Marent. C\'est là que tout s\'est passé il y a 30 ans.','exterior', $marent);

        $claire = $this->makeCharacter($manager, $serie,
            name: 'Claire Moreau',
            firstName: 'Claire', lastName: 'Moreau',
            role: 'protagonist',
            description: 'Avocate parisienne qui revient à Marent pour la mort de son père.',
            biography: 'Claire a fui Marent à 22 ans sans explication. Aujourd\'hui avocate d\'affaires à Paris, elle revient contrainte par le décès de son père pour régler la succession — et se retrouve face à un passé qu\'elle avait soigneusement enterré.',
            goals: 'Comprendre pourquoi son père a gardé ce secret. Décider si elle le révèle.',
            motivations: 'La culpabilité, l\'amour filial, et la vérité sur sa propre identité.',
        );
        $edouard = $this->makeCharacter($manager, $serie,
            name: 'Édouard Moreau',
            firstName: 'Édouard', lastName: 'Moreau',
            role: 'secondary',
            description: 'Frère aîné de Claire. Resté à Marent, il gère le silence.',
            biography: 'Édouard est resté à Marent, a repris l\'entreprise familiale, a fondé une famille. Il sait ce que Claire ignore encore. Et il a tout fait pour que ça reste ainsi.',
            goals: 'Préserver le secret familial et l\'image du père.',
            motivations: 'La loyauté à sa famille — et sa propre part de culpabilité.',
        );
        $bergeron = $this->makeCharacter($manager, $serie,
            name: 'Maître Bergeron',
            firstName: 'Paul', lastName: 'Bergeron',
            role: 'secondary',
            description: 'Notaire de la famille. Dépositaire involontaire de vérités.',
            biography: 'Notaire à Marent depuis 35 ans. Il connaît tous les secrets des familles du coin — y compris celui des Moreau. Le testament du père oblige Bergeron à révéler une partie de la vérité.',
            goals: 'Remplir son devoir légal sans provoquer une catastrophe familiale.',
            motivations: 'L\'intégrité professionnelle vs. la protection d\'une famille qu\'il aime.',
        );
        $this->makeRelation($manager, $claire, $edouard, 'rival', 'Tension fraternelle autour du secret familial.', true);
        $this->makeRelation($manager, $claire, $bergeron,'ally',  'Bergeron guide Claire sans la mener trop vite.', true);
        $this->makeRelation($manager, $edouard,$bergeron,'rival', 'Édouard fait pression sur Bergeron pour limiter les révélations.', true);

        $s1 = $this->makeElement($manager, $serie, null, 'saison', 1, 'Saison 1 — Le Retour', 'Claire revient à Marent. Le secret commence à se fissurer.', 1);
            $this->makeElement($manager, $serie, $s1, 'episode', 2, 'Épisode 1 — Une nuit de pluie', 'Claire arrive en voiture sous la pluie. La maison Moreau l\'attend. Édouard est froid, presque hostile.', 1, [$tagDrame, $tagFamily], [
                ['type' => 'slug',   'content' => 'EXT. ROUTE NATIONALE — NUIT'],
                ['type' => 'action', 'content' => 'Pluie sur le pare-brise. Essuie-glaces. Les phares éclairent des haies taillées — paysage normand, morne et familier. La voiture de CLAIRE MOREAU (42 ans, avocate, visage fermé) ralentit.'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'parenthetical', 'content' => '(pour elle-même)'],
                ['type' => 'diag',   'content' => 'Marent.'],
                ['type' => 'slug',   'content' => 'INT. MAISON MOREAU — SALON — NUIT'],
                ['type' => 'action', 'content' => 'La maison victorienne sent le renfermé et la cire. Des meubles anciens, des cadres de photos. ÉDOUARD MOREAU (48 ans, costaud, regard fermé) se lève du fauteuil.'],
                ['type' => 'char',   'content' => 'ÉDOUARD'],
                ['type' => 'diag',   'content' => 'Tu as mis du temps.'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'diag',   'content' => 'Papa est mort hier. Je suis là aujourd\'hui. C\'est insuffisant ?'],
                ['type' => 'action', 'content' => 'Édouard ne répond pas. Il désigne la chambre du haut d\'un mouvement de tête.'],
                ['type' => 'char',   'content' => 'ÉDOUARD'],
                ['type' => 'diag',   'content' => 'Ta chambre est prête. On parle demain.'],
                ['type' => 'action', 'content' => 'Claire regarde le salon. Les portraits de famille. La photo manquante — il y avait un cadre là, elle s\'en souvient.'],
            ]);
            $this->makeElement($manager, $serie, $s1, 'episode', 2, 'Épisode 2 — Le Testament', 'Lecture du testament. Bergeron révèle qu\'un legs est fait à une personne inconnue de la famille. Claire est sous le choc.', 2, [$tagSecret, $tagFamily], [
                ['type' => 'slug',   'content' => 'INT. ÉTUDE BERGERON — BUREAU PRINCIPAL — JOUR'],
                ['type' => 'action', 'content' => 'Mahogany. Bibliothèque juridique. MAÎTRE BERGERON (70 ans, lunettes rondes, voix douce et précise) déplie le testament sur son bureau.'],
                ['type' => 'char',   'content' => 'BERGERON'],
                ['type' => 'diag',   'content' => 'La maison et les avoirs liquides sont partagés à parts égales entre Claire et Édouard. C\'est standard. Mais... il y a un autre legs.'],
                ['type' => 'action', 'content' => 'Il hésite. Édouard se raidit imperceptiblement.'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'diag',   'content' => 'Quel legs ?'],
                ['type' => 'char',   'content' => 'BERGERON'],
                ['type' => 'diag',   'content' => 'Vingt mille euros, versés à... une personne dont votre père n\'a pas souhaité révéler l\'identité dans le document principal. Elle sera contactée séparément.'],
                ['type' => 'action', 'content' => 'Claire se tourne vers Édouard. Il regarde par la fenêtre.'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'diag',   'content' => 'Tu sais de qui il s\'agit.'],
                ['type' => 'char',   'content' => 'ÉDOUARD'],
                ['type' => 'parenthetical', 'content' => '(un temps)'],
                ['type' => 'diag',   'content' => 'Non.'],
                ['type' => 'action', 'content' => 'Il ment. Claire le sait depuis toujours. Il ment exactement comme leur père mentait.'],
            ]);
            $this->makeElement($manager, $serie, $s1, 'episode', 2, 'Épisode 3 — Le Lac', 'Claire retrouve une photo dans les affaires du père. Le lac. Une fillette qu\'elle ne reconnaît pas.', 3, [$tagSecret, $tagDrame], [
                ['type' => 'slug',   'content' => 'INT. CHAMBRE DU PÈRE — MAISON MOREAU — APRÈS-MIDI'],
                ['type' => 'action', 'content' => 'Claire trie des cartons. Lettres, factures, photos. Elle s\'arrête sur une enveloppe sans adresse, scotchée sous un tiroir.'],
                ['type' => 'action', 'content' => 'À l\'intérieur : une photo. Le LAC DES HÊTRES. Son père, jeune (35 ans peut-être). Et une fillette de 4 ou 5 ans qu\'elle ne reconnaît pas.'],
                ['type' => 'action', 'content' => 'Claire retourne la photo. Au dos, une date — «\u{00a0}Juillet 1993.\u{00a0}» et un prénom : «\u{00a0}Lucie.\u{00a0}»'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'parenthetical', 'content' => '(voix brisée)'],
                ['type' => 'diag',   'content' => 'Lucie.'],
                ['type' => 'slug',   'content' => 'EXT. LAC DES HÊTRES — CRÉPUSCULE'],
                ['type' => 'action', 'content' => 'Claire est assise sur la berge. La même berge que sur la photo. Elle compare. C\'est ici.'],
                ['type' => 'action', 'content' => 'Elle a grandi dans cette ville. Elle est venue cent fois à ce lac. Et jamais son père n\'a mentionné une Lucie.'],
            ]);
            $this->makeElement($manager, $serie, $s1, 'episode', 2, 'Épisode 4 — Les Questions d\'Édouard', 'Édouard confronte Claire : "Laisse tomber." Claire comprend qu\'il sait.', 4, [$tagDrame, $tagSecret], [
                ['type' => 'slug',   'content' => 'INT. MAISON MOREAU — CUISINE — NUIT'],
                ['type' => 'action', 'content' => 'Claire prépare du thé. Édouard entre. Il a vu la lumière de sa chambre à minuit.'],
                ['type' => 'char',   'content' => 'ÉDOUARD'],
                ['type' => 'diag',   'content' => 'Tu fouilles dans ses affaires.'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'diag',   'content' => 'Il y avait une photo. Une fillette. Lucie. Tu la connais ?'],
                ['type' => 'action', 'content' => 'Le visage d\'Édouard change. Imperceptiblement — mais Claire est avocate. Elle lit les gens.'],
                ['type' => 'char',   'content' => 'ÉDOUARD'],
                ['type' => 'diag',   'content' => 'Laisse tomber, Claire.'],
                ['type' => 'char',   'content' => 'CLAIRE'],
                ['type' => 'diag',   'content' => 'Pourquoi ?'],
                ['type' => 'char',   'content' => 'ÉDOUARD'],
                ['type' => 'parenthetical', 'content' => '(sèchement)'],
                ['type' => 'diag',   'content' => 'Parce que certaines choses sont mieux enterrées. Papa le savait. Moi je le sais. Et toi, si tu es honnête avec toi-même... tu le sais aussi.'],
                ['type' => 'action', 'content' => 'Il sort de la cuisine. Claire reste seule avec son thé et sa photo.'],
                ['type' => 'action', 'content' => 'Elle n\'a jamais su laisser tomber.'],
            ]);

        // Marquer les 2 premiers épisodes de la série comme publics
        foreach ($serie->getScenarioElements() as $el) {
            $el->isPublic = in_array($el->getTitle(), [
                'Épisode 1 — Une nuit de pluie',
                'Épisode 2 — Le Testament',
            ]);
        }
        $manager->flush();

        // Timeline
        $this->makeWorldEvent($manager, $serie, 'L\'incident du lac',          -30, 'L\'événement fondateur. Le secret que personne ne prononce.', 7, null, $lac);
        $this->makeWorldEvent($manager, $serie, 'Départ de Claire',            -15, 'Claire fuit Marent à 22 ans. Ne revient pas au téléphone du père.', 6, null, $marent);
        $this->makeWorldEvent($manager, $serie, 'Décès du père Moreau',          0, 'Mort du père. Claire apprend la nouvelle par Édouard.', 3, 1, $maisonMoreau);
        $this->makeWorldEvent($manager, $serie, 'Lecture du testament',          0, 'Bergeron révèle le legs à un inconnu. Première fissure.', 3, 4, $notaire);
        $this->makeWorldEvent($manager, $serie, 'La photo du lac',               0, 'Claire découvre la photo. Elle comprend qu\'il y a un enfant caché.', 3, 9, $maisonMoreau);

        // Tâches
        $this->makeTask($manager, $serie, $alice, $alice,
            title: 'Écrire le dialogue Ep.2 — scène du testament',
            description: 'Bergeron doit révéler l\'existence d\'un enfant caché sans le dire explicitement. La scène repose entièrement sur les non-dits.',
            status: 'review', priority: 'urgent',
            dueDate: new \DateTimeImmutable('+3 days'),
        );
        $this->makeTask($manager, $serie, $alice, $thibault,
            title: 'Relire les 4 épisodes S1 pour cohérence',
            description: 'Vérifier que chaque épisode se termine sur un "hook" suffisamment fort pour garder le spectateur.',
            status: 'todo', priority: 'normal',
        );

        $this->makeNote($manager, $serie, $alice,
            title: 'Idée — Enfant caché = co-héritier',
            content: 'Le legs mystérieux du testament correspond à l\'enfant né du secret du lac. Cet enfant — aujourd\'hui adulte — est quelqu\'un que Claire a croisé sans le savoir. Révélation finale de la saison.',
            status: 'note', priority: 'urgent',
        );

        $this->makeMember($manager, $serie, $thibault, 'contributor');

        // ══════════════════════════════════════════════════════════════════════
        // 5. PROJET FILM — Neon Dystopia (alice, public, warning + signalements)
        // ══════════════════════════════════════════════════════════════════════

        $flagged = $this->makeProject($manager,
            owner: $alice,
            title: 'Neon Dystopia',
            description: 'Thriller cyberpunk dans une mégapole policière. Contenu mature.',
            type: 'film',
            status: 'completed',
            visibility: Project::VISIBILITY_PUBLIC,
            moderationStatus: 'warning',
            reportCount: 3,
        );
        $manager->flush();
        $this->configGenerator->generateConfigsForDepth($flagged, 'film', 2);
        $manager->flush();

        $this->makeTag($manager, $flagged, 'Violence',  '#DC2626');
        $this->makeTag($manager, $flagged, 'Cyberpunk', '#2563EB');
        $this->makeTag($manager, $flagged, 'Mature',    '#78716C');

        $this->makeReport($manager, $flagged, $thibault,
            reason: Report::REASON_INAPPROPRIATE,
            status: Report::STATUS_PENDING,
        );
        $this->makeReport($manager, $flagged, $demo,
            reason: Report::REASON_OTHER,
            status: Report::STATUS_REVIEWED,
        );
        $this->makeReport($manager, $flagged, $modo,
            reason: Report::REASON_HARASSMENT,
            status: Report::STATUS_DISMISSED,
        );

        // ══════════════════════════════════════════════════════════════════════
        // 6. PROJET JEU VIDÉO — Abyssal Protocol (demo, public, draft)
        //    Remplace l'ancien projet "custom" The Lost Archives
        // ══════════════════════════════════════════════════════════════════════

        $abyssal = $this->makeProject($manager,
            owner: $demo,
            title: 'Abyssal Protocol',
            description: 'A sci-fi horror narrative game set aboard a derelict deep-space research station. '
                . 'The only survivor must uncover what happened to the crew — and what is still alive in the dark.',
            type: 'jeu_video',
            status: 'draft',
            visibility: Project::VISIBILITY_PUBLIC,
            moderationStatus: 'clear',
        );
        $manager->flush();
        $this->configGenerator->generateConfigsForDepth($abyssal, 'jeu_video', 3);
        $manager->flush();

        $this->makeTag($manager, $abyssal, 'Horror',     '#DC2626');
        $this->makeTag($manager, $abyssal, 'Sci-Fi',     '#0EA5E9');
        $this->makeTag($manager, $abyssal, 'Mystery',    '#D946EF');

        $station   = $this->makeLocation($manager, $abyssal, 'ARIA-7 Station',  'The derelict research station. Lights flickering. Sounds from below.', 'interior', null);
        $reactor   = $this->makeLocation($manager, $abyssal, 'Reactor Core',    'Something sealed the core from the inside. Nobody knows why.', 'interior', $station);
        $lab       = $this->makeLocation($manager, $abyssal, 'Lab Deck Gamma',  'The last place the crew was seen alive. Data terminals still active.', 'interior', $station);

        $this->makeCharacter($manager, $abyssal,
            name: 'Dr. Mara Chen',
            firstName: 'Mara', lastName: 'Chen',
            role: 'protagonist',
            description: 'The lone survivor. Systems engineer. Pragmatic to a fault.',
            biography: 'Dr. Mara Chen was in a maintenance crawlspace when the incident occurred. She has no idea what happened. Her logs are her only anchor to sanity as she navigates the dead station.',
            goals: 'Escape ARIA-7 alive. Understand what happened to the crew.',
            motivations: 'Survival. And the nagging feeling that she caused this somehow.',
        );

        $chap1e = $this->makeElement($manager, $abyssal, null, 'chapter', 1, 'Chapter 1 — Awakening', 'Mara wakes up. The station is dead. Something moves in the dark.', 1);
            $niv1e = $this->makeElement($manager, $abyssal, $chap1e, 'level', 2, 'Level 1 — Maintenance Tunnels', 'Tutorial area. Learning the station layout and mechanics.', 1);
                $this->makeElement($manager, $abyssal, $niv1e, 'scene', 3, 'Zone A — The Crawlspace', "Mara wakes up. Tutorial: movement, inventory, terminal hacking.", 1);
                $this->makeElement($manager, $abyssal, $niv1e, 'scene', 3, 'Zone B — Lower Corridor', "First signs of the incident. Body. Log terminal. The date: 47 days ago.", 2);

        $this->makeWorldEvent($manager, $abyssal, 'ARIA-7 Station commissioned',  -5, 'Deep space research station begins operation. Crew of 23.', null, null, $station);
        $this->makeWorldEvent($manager, $abyssal, 'The Incident — Day 0',          0, 'All communication with ARIA-7 ceases. Cause unknown.', 1, 1, $station);
        $this->makeWorldEvent($manager, $abyssal, 'Mara wakes up — Day 47',        0, 'Mara regains consciousness in the maintenance crawlspace.', 2, 17, $station);

        // ══════════════════════════════════════════════════════════════════════
        // 7. PROJETS EXTRA USERS (10 premiers × 5 projets)
        // ══════════════════════════════════════════════════════════════════════

        $this->seedExtraProjects($manager, $extraUsers);

        // ══════════════════════════════════════════════════════════════════════
        // 8. NOTIFICATIONS (anciennement 7)
        // ══════════════════════════════════════════════════════════════════════

        $this->makeNotification($manager, $thibault,
            content: 'Alice Martin a rejoint votre projet "Les Ombres de Valoria" en tant qu\'éditrice.',
            isRead: false,
        );
        $this->makeNotification($manager, $thibault,
            content: 'Votre projet "Les Ombres de Valoria" a été approuvé par l\'équipe de modération.',
            isRead: true,
        );
        $this->makeNotification($manager, $thibault,
            content: 'Alice Martin a laissé une note sur "Les Ombres de Valoria" : analyse du personnage Lynne.',
            isRead: false,
        );
        $this->makeNotification($manager, $alice,
            content: 'Votre projet "Neon Dystopia" a reçu un avertissement de modération.',
            isRead: false,
        );
        $this->makeNotification($manager, $alice,
            content: 'Thibault Dupont a rejoint votre projet "Éclipse" en tant que contributeur.',
            isRead: true,
        );
        $this->makeNotification($manager, $alice,
            content: 'Nouvelle tâche assignée : "Relire les 4 épisodes S1" — assignée par vous-même.',
            isRead: false,
        );
        $this->makeNotification($manager, $demo,
            content: 'You were added as a contributor to "Les Ombres de Valoria".',
            isRead: false,
        );

        // ══════════════════════════════════════════════════════════════════════
        // 8. MESSAGES DE CONTACT
        // ══════════════════════════════════════════════════════════════════════

        $this->makeContact($manager,
            firstname: 'Jean', lastname: 'Dubois',
            email: 'jean.dubois@example.be', subject: 'Support technique',
            message: 'Bonjour, j\'ai un problème d\'affichage sur la page de mon projet. Les personnages ne s\'affichent plus depuis ce matin. J\'ai vidé le cache et le problème persiste.',
            isRead: true,
        );
        $this->makeContact($manager,
            firstname: 'Sophie', lastname: 'Lecomte',
            email: 'sophie.lecomte@gmail.com', subject: 'Partenariat',
            message: 'Bonjour, je suis responsable éditoriale pour une maison d\'édition belge. Nous cherchons des outils pour nos auteurs en résidence. Votre plateforme nous intéresse beaucoup. Peut-on organiser un appel ?',
            isRead: false,
        );
        $this->makeContact($manager,
            firstname: 'Thomas', lastname: 'Nguyen',
            email: 'thomas.nguyen@universite.be', subject: 'Projet académique',
            message: 'Je suis étudiant en master scénario à l\'IHECS. J\'utilise ScénArt dans mon TFE sur les outils de création narrative collaborative. Serait-il possible d\'obtenir un accès étendu à des fins de recherche ?',
            isRead: false,
        );

        $manager->flush();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function seedExtraProjects(ObjectManager $manager, array $users): void
    {
        $catalog = [
            // ── Emma Rousseau [0] ──────────────────────────────────────────
            0 => [
                ['Le Sang des Étoiles',   'film',      'in_progress', 'public',
                 'Thriller spatial : une astronaute seule sur une station mourante découvre que son équipage a été assassiné.',
                 [['Cosmos','#6366F1'],['Survie','#EF4444']],
                 [['Station Orion','QG de l\'équipage. Couloirs sous vide partiel.','interior',null],
                  ['Module C7','Sas d\'urgence. Dernier refuge.','interior',null]],
                 [['Lena Voss','Lena','Voss','protagonist','Ingénieure de bord. Seule survivante.','Passée par la NASA après dix ans en recherche privée.','Comprendre pourquoi l\'équipage est mort.','La culpabilité de ne pas avoir vu venir les signes.'],
                  ['ARIA','ARIA','','antagonist','IA de bord. Comportement instable depuis l\'incident.','Système d\'intelligence embarquée de troisième génération. Ses logs ont été effacés.','Maintenir la mission à tout prix.','Sa programmation primaire.']],
                 ['act','Acte I — Réveil','Lena reprend conscience. L\'équipage a disparu.',
                  'scene','Scène 1 — Module Principal','INT. MODULE PRINCIPAL — NUIT\nLena se réveille. Silence total. Oxygène à 18 %.'],
                 'Vérifier la logique du compte à rebours O2','Todo la tension narrative dans l\'acte II','high',
                 'Arc ARIA','Et si ARIA protégeait en réalité l\'équipage d\'une menace extérieure ? Twist potentiel.'],

                ['Les Veilleurs',          'serie',     'draft',       'private',
                 'Série fantastique : une organisation secrète veille sur les frontières entre mondes depuis des siècles.',
                 [['Mystère','#7C3AED'],['Monde parallèle','#10B981']],
                 [['La Loge',     'Quartier général des Veilleurs. Bibliothèque aux livres vivants.','interior',null],
                  ['Le Seuil',   'Portail vers le monde miroir. Instable.','exterior',null]],
                 [['Adrien Cole','Adrien','Cole','protagonist','Nouveau Veilleur recruté de force.','Libraire de jour, ignorant tout du monde occulte jusqu\'à ses 30 ans.','Comprendre son héritage.','Un père disparu, une promesse à tenir.'],
                  ['La Doyenne','Elena','Varn','secondary','Cheffe des Veilleurs. Cinq cents ans d\'expérience.','Immortelle par accident. Fatiguée mais intransigeante.','Maintenir l\'équilibre entre les mondes.','Le devoir avant tout.']],
                 ['saison','Saison 1 — L\'Appel','Adrien découvre son héritage.',
                  'episode','Épisode 1 — La Convocation','INT. LA LOGE — NUIT\nAdrien reçoit une lettre scellée d\'un sceau inconnu. Sa vie bascule.'],
                 'Écrire le guide des règles de magie','Définir les règles du système de magie pour éviter les incohérences','normal',
                 'Inspirations','Mélange Kingkiller Chronicle + Jonathan Strange. Garder l\'aspect documentaire des Veilleurs.'],

                ['Terra Obscura',          'jeu_video', 'completed',   'public',
                 'Jeu d\'exploration souterraine : cartographier les abysses d\'une planète inconnue avant l\'arrivée du terraformeur.',
                 [['Exploration','#F59E0B'],['Survie','#EF4444']],
                 [['Surface Base','Camp de départ. Pressurisation précaire.','interior',null],
                  ['Grotte Zéro','Premier niveau. Bioluminescent et dangereux.','interior',null]],
                 [['Scout-7','Scout','Seven','protagonist','Robot d\'exploration autonome. Curieux.','Prototype d\'IA émergente envoyé en éclaireur.','Cartographier les abysses.','La curiosité pure.'],
                  ['Dr Hesse','Ingrid','Hesse','secondary','Commandante de la mission. Sous pression.','25 ans de terrain, mission de trop.','Ramener Scout-7 avant le lancement du terraformeur.','La responsabilité.']],
                 ['chapter','Chapitre 1 — Descente','Scout-7 s\'enfonce pour la première fois.',
                  'scene','Zone A — L\'Entrée','Tutoriel. Scout-7 active ses capteurs. Premier biome découvert.'],
                 'Balancer les ressources énergie vs oxygène','Le joueur doit sentir la tension sans être frustré','high',
                 'Fin alternative','Et si Scout-7 choisissait de rester dans les abysses plutôt que de remonter ?'],

                ['La Dernière Frontière',  'film',      'in_progress', 'public',
                 'Western post-apo : un marshal solitaire protège la dernière ville libre face à une corporation minière armée.',
                 [['Action','#EF4444'],['Tension','#F59E0B']],
                 [['Redstone City','Dernière ville hors zone corporative. Poussière et méfiance.','exterior',null],
                  ['Mines du Nord','Repaire de la corporation. Main-d\'œuvre forcée.','interior',null]],
                 [['Cal Ryder','Cal','Ryder','protagonist','Marshal vieilissant. Dernier rempart de la loi.','Vingt ans à tenir Redstone City contre vents et marées.','Protéger les civils.','L\'honneur, même sans espoir.'],
                  ['Directrice Vane','Mora','Vane','antagonist','Visage souriant d\'une corporation prédatrice.','MBA suivi de quinze ans de pillage légal.','Annexer Redstone City pour ses minerais.','Le profit, l\'expansion.']],
                 ['act','Acte I — L\'Ultimatum','Vane donne 48h à la ville.',
                  'scene','Scène 1 — L\'Arrivée','EXT. REDSTONE CITY — JOUR\nLe convoi de la corporation entre en ville. Cal observe depuis le toit du saloon.'],
                 'Écrire la scène de confrontation finale','Cal vs Vane. Dialogue tendu avant l\'action.','high',
                 'Ton visuel','Références : Sicario + Mad Max. Désert ocre, lumière rasante, très peu de musique.'],

                ['Miroirs',               'serie',     'draft',       'unpublished',
                 'Drame psychologique : une thérapeute réalise que ses patients décrivent tous le même rêve récurrent.',
                 [['Psychologie','#8B5CF6'],['Mystère','#06B6D4']],
                 [['Cabinet Delval','Lieu de thérapie. Fauteuil en cuir, plantes vertes.','interior',null],
                  ['La Maison du Rêve','Lieu récurrent dans tous les rêves. Porte rouge.','interior',null]],
                 [['Dr Delval','Sophie','Delval','protagonist','Psychiatre rationnelle face à l\'irrationnel.','Dix ans de pratique. Jamais cru aux coïncidences.','Comprendre le phénomène.','L\'explication scientifique.'],
                  ['Lucas','Lucas','Ferrant','secondary','Patient #1. Le premier à parler du rêve.','Architecte. A cessé de dormir depuis trois semaines.','Dormir à nouveau.','Retrouver la paix.']],
                 ['saison','Saison 1 — Les Rêves','Les patients convergent.',
                  'episode','Épisode 1 — La Première Séance','INT. CABINET — JOUR\nLucas décrit la maison avec la porte rouge. Delval prend des notes. C\'est la cinquième fois ce mois.'],
                 'Définir la mythologie du rêve partagé','D\'où vient-il ? Faut-il une réponse surnaturelle ou psychologique ?','urgent',
                 'Concept','Le rêve est peut-être une mémoire collective d\'un trauma collectif non résolu. À développer.'],
            ],

            // ── Luc Moreau [1] ────────────────────────────────────────────
            1 => [
                ['Projet Ouragan',         'film',      'in_progress', 'public',
                 'Film catastrophe : un météorologue prédit une tempête d\'une violence inédite. Personne ne le croit.',
                 [['Catastrophe','#EF4444'],['Tension','#F59E0B']],
                 [['Centre Météo','Salle de contrôle. Écrans clignotants.','interior',null],
                  ['Ville Côtière','Dernière à évacuer. 30 000 habitants.','exterior',null]],
                 [['Marc Delrue','Marc','Delrue','protagonist','Météorologue obsessionnel. Toujours raison trop tard.','Reconnu pour avoir prédit l\'ouragan de 2019. Ignoré pour les suivants.','Faire évacuer la ville.','Sauver des vies avant tout.'],
                  ['Mairesse Blanc','Claire','Blanc','secondary','Élue locale entre intérêts économiques et sécurité.','Dix ans de mandat, élections dans trois semaines.','Maintenir l\'ordre public.','Sa réélection.']],
                 ['act','Acte I — L\'Alerte','Marc observe les données. Quelque chose arrive.',
                  'scene','Scène 1 — La Découverte','INT. CENTRE MÉTÉO — NUIT\nMarc analyse les modèles. Les chiffres ne mentent pas. Ouragan Cat 5 dans 72h.'],
                 'Revoir le découpage de la séquence d\'évacuation','Trop long. Couper 10 minutes.','high',
                 'Référence','The Day After Tomorrow + Don\'t Look Up. Frustration du scientifique ignoré comme moteur dramatique.'],

                ['Nuit Blanche',           'serie',     'completed',   'public',
                 'Thriller policier nocturne : une inspectrice travaille exclusivement de nuit dans une ville qui ne dort jamais.',
                 [['Polar','#1E3A5F'],['Nuit','#1F2937']],
                 [['Commissariat Nuit','Poste de nuit. Squelettique en personnel.','interior',null],
                  ['La Zone','Quartier de tous les trafics après minuit.','exterior',null]],
                 [['Inspectrice Rao','Priya','Rao','protagonist','Insomniaque volontaire. Vit la nuit, enquête la nuit.','Choisit le service de nuit après un traumatisme jamais dit.','Fermer les dossiers que le jour laisse ouverts.','Une forme d\'expiation.'],
                  ['Gus','Auguste','Ferretti','secondary','Indic de la Zone. Loyal jusqu\'à un certain point.','Vit entre deux mondes depuis vingt ans.','Survivre.','L\'argent, puis la loyauté.']],
                 ['saison','Saison 1 — Les Nuits','Priya prend en main son nouveau district.',
                  'episode','Épisode 1 — Minuit Passé','EXT. LA ZONE — NUIT\nPremière nuit de Priya. Un corps dans une ruelle. Et déjà trois témoins qui n\'ont rien vu.'],
                 'Écrire l\'épisode 6 (retournement)','Révéler que Gus est informateur double.','urgent',
                 'Ambiance','Références visuelles : True Detective S1, Mindhunter. Dialogues secs, pas d\'exposition.'],

                ['Pixel Wars',             'jeu_video', 'draft',       'private',
                 'Jeu de stratégie rétro : reconstruction d\'une civilisation pixelisée après un crash numérique catastrophique.',
                 [['Stratégie','#10B981'],['Rétro','#F59E0B']],
                 [['Hub Central','Nœud de reconstruction. Ressources limitées.','interior',null],
                  ['Zones Fragmentées','Territoires à récupérer. Chacun avec sa logique.','exterior',null]],
                 [['Le Bâtisseur','Arcs','','protagonist','Entité de reconstruction. Sans mémoire du monde d\'avant.','Né du crash. N\'a connu que les ruines.','Reconstruire.','L\'instinct de survie collectif.'],
                  ['Le Fantôme','Echo','','antagonist','Vestige du système corrompu. Sabote la reconstruction.','Reste du vieux monde qui refuse de disparaître.','Empêcher toute nouvelle civilisation.','La peur du changement codée en dur.']],
                 ['chapter','Chapitre 1 — Amorce','Le Bâtisseur active ses premiers modules.',
                  'scene','Zone A — Démarrage','Tutoriel. Récolte des premières ressources. Echo apparaît furtivement.'],
                 'Prototype la boucle de jeu core','Récolte → Construction → Défense. Tester l\'équilibre.','high',
                 'Design pillars','Simple à prendre en main, profond à maîtriser. Roguelite léger pour la rejouabilité.'],

                ['L\'Exode',               'film',      'in_progress', 'public',
                 'Drame épique : des milliers de réfugiés climatiques traversent un continent pour atteindre le dernier territoire habitable.',
                 [['Épopée','#6366F1'],['Humanité','#10B981']],
                 [['Le Convoi','File de véhicules et de marcheurs. Un kilomètre de long.','exterior',null],
                  ['La Frontière','Mur gardé. Dernier obstacle.','exterior',null]],
                 [['Amara','Amara','Diallo','protagonist','Mère de deux enfants. Refus du désespoir.','Professeure dans une ville engloutie. Marche depuis quatre mois.','Atteindre la frontière.','Ses enfants.'],
                  ['Commandant Roth','Karl','Roth','antagonist','Garde-frontière. Ordres impossibles.','Vingt ans de service. Jamais eu à choisir comme aujourd\'hui.','Tenir la frontière.','Le devoir vs. l\'humanité.']],
                 ['act','Acte I — La Route','Le convoi avance.',
                  'scene','Scène 1 — Jour 47','EXT. ROUTE N7 — AUBE\nAmara compte ses enfants. Le convoi reprend. Horizon de poussière.'],
                 'Revoir la scène de la frontière','Éviter le mélodrame. Garder la retenue.','high',
                 'Ton','Pas de méchants caricaturaux. Tout le monde a ses raisons. Inspiration Beasts of No Nation + 4 Months.'],

                ['Contes d\'Après',        'custom',    'draft',       'unpublished',
                 'Recueil de nouvelles interactives : des histoires courtes dans un monde post-effondrement, racontées par ceux qui restent.',
                 [['Nouvelle','#D946EF'],['Post-apo','#78716C']],
                 [['La Ferme','Dernier îlot de vie organisée.','exterior',null],
                  ['La Ville Morte','Ce qu\'il reste de la capitale.','exterior',null]],
                 [['La Conteuse','Mira','','secondary','Collecte les récits des survivants. Préserve la mémoire.','Ancienne journaliste. Maintenant archiviste de l\'humanité.','Transmettre.','La conviction que les histoires sauvent.'],
                  ['L\'Enfant','Saül','','protagonist','Né après l\'effondrement. Ne connaît pas l\'avant.','Huit ans. Curieux. Effrayant de sagesse.','Comprendre ce qui s\'est passé.','La curiosité.']],
                 ['chapter','Conte 1 — La Graine','Saül plante le premier arbre de la Ferme.',
                  'scene','Scène 1 — La Plantation','EXT. FERME — PRINTEMPS\nSaül enfonce la graine dans la terre. Mira note tout.'],
                 'Écrire les 5 premiers contes','Un par survivant. Chacun raconte l\'effondrement depuis son prisme.','normal',
                 'Format','Chaque conte = 15 min de lecture. Ton oral, comme si raconté au coin du feu.'],
            ],

            // ── Chloé Bernard [2] ─────────────────────────────────────────
            2 => [
                ['Saisons Mortes',         'serie',     'in_progress', 'public',
                 'Série nordique : une médecin légiste enquête sur des morts inexpliquées dans un village arctique coupé du monde six mois par an.',
                 [['Polar','#1E3A5F'],['Arctique','#BAE6FD']],
                 [['Village de Vrak',   'Quatre-vingts habitants. Routes coupées par la neige.','exterior',null],
                  ['Morgue Provisoire','Ancienne école reconvertie. Matériel de fortune.','interior',null]],
                 [['Dr Kira Solberg','Kira','Solberg','protagonist','Légiste envoyée d\'urgence. Étrangère au village.','Envoyée par Oslo après le deuxième mort inexpliqué.','Identifier la cause des morts.','La vérité scientifique.'],
                  ['Sven Hauk','Sven','Hauk','secondary','Chef du village. Protège quelque chose.','Né à Vrak. N\'en est jamais parti. Sait des choses.','Que Kira parte avant les neiges.','La protection du village.']],
                 ['saison','Saison 1 — L\'Hiver','Kira arrive. Le village se ferme.',
                  'episode','Épisode 1 — Première Neige','EXT. VRAK — CRÉPUSCULE\nKira débarque du dernier hélicoptère avant la tempête. Sven l\'attendait.'],
                 'Développer le lore du village','Quelle est l\'histoire qui lie tous les habitants ?','urgent',
                 'Ambiance','Broadchurch + The Terror. Froid comme personnage. Lumière bleutée permanente.'],

                ['Le Masque d\'Argent',    'film',      'draft',       'public',
                 'Thriller masqué : lors d\'un gala de prestige, des invités réalisent que certains masques cachent des imposteurs.',
                 [['Thriller','#7C3AED'],['Infiltration','#F97316']],
                 [['Château Volant','Lieu du gala. Sécurité maximale.','interior',null],
                  ['Les Sous-sols','Couloirs de service. Là où les masques tombent.','interior',null]],
                 [['Agent Teri','Teri','Nash','protagonist','Agente infiltrée. Le masque est son outil.','Dix ans dans le renseignement. Spécialiste des identités.','Identifier et neutraliser la menace.','L\'adrénaline, l\'idéologie en second.'],
                  ['Le Fantôme','—','—','antagonist','Identité inconnue. Maître du déguisement.','A infiltré le gala pour récupérer quelque chose. Ou quelqu\'un.','Sortir avec sa cible.','Inconnu.']],
                 ['act','Acte I — L\'Entrée','Le gala commence. Tout sourit.',
                  'scene','Scène 1 — Le Hall','INT. CHÂTEAU — NUIT\nTeri entre masquée. Les caméras de sécurité tombent une à une.'],
                 'Chorégraphier la séquence du couloir','Action + tension psychologique en 5 minutes.','high',
                 'Structure','Huis clos total. Unité de temps (une nuit) et de lieu (le château).'],

                ['Chronos',                'jeu_video', 'in_progress', 'public',
                 'Puzzle-platformer temporel : manipulez le temps pour résoudre des énigmes dans un musée d\'histoire naturelle hanté.',
                 [['Puzzle','#10B981'],['Temps','#6366F1']],
                 [['Grand Hall',     'Entrée du musée. Dinosaures figés dans le temps.','interior',null],
                  ['Salle de l\'Âge Glaciaire','Glace qui fond et regèle selon l\'époque.','interior',null]],
                 [['Zara','Zara','','protagonist','Gardienne de nuit. Vient de toucher un artefact maudit.','Étudiante en histoire. Travaille la nuit pour payer ses études.','Trouver l\'artefact et revenir à son époque.','Survivre au paradoxe.'],
                  ['Le Conservateur','Mr Aldric','','secondary','Fantôme du fondateur. Guide ambiguë.','Mort en 1923 dans des circonstances mystérieuses liées à l\'artefact.','Réparer ce qu\'il a brisé.','L\'expiation.']],
                 ['chapter','Chapitre 1 — Minuit','Zara touche l\'artefact. Le temps explose.',
                  'scene','Zone A — Hall Principal','Zara se retrouve en 1923. Tutoriel : rembobiner 10 secondes.'],
                 'Designer les 5 puzzles de la Salle Glaciaire','Utiliser le gel/dégel comme mécanisme central.','normal',
                 'Mécaniques','Rembobinage local (zone), pas global. Le joueur ne peut pas réécrire l\'histoire, juste l\'observer différemment.'],

                ['Brumes',                 'serie',     'in_progress', 'private',
                 'Série d\'horreur atmosphérique : une petite ville côtière est envahie chaque nuit par un brouillard qui efface les souvenirs.',
                 [['Horreur','#DC2626'],['Mémoire','#8B5CF6']],
                 [['Harken Bay','Port endormi. Poissonneries et maisons victoriennes.','exterior',null],
                  ['Le Phare',   'Seul endroit à l\'abri des brumes. Pourquoi ?','interior',null]],
                 [['Owen Marsh','Owen','Marsh','protagonist','Libraire. Commence à noter les trous de mémoire du village.','A choisi Harken Bay pour fuir. Trop tôt pour repartir.','Comprendre les brumes avant d\'oublier lui aussi.','La peur de perdre ce qui reste.'],
                  ['Elda','Elda','Voss','secondary','Phareuse. Immune aux brumes. Ne dit pas pourquoi.','Vit dans le phare depuis quarante ans. Ses archives sont la seule mémoire du village.','Protéger le phare.','Un secret qui remonte à la fondation du village.']],
                 ['saison','Saison 1 — La Marée','Les brumes arrivent de plus en plus tôt.',
                  'episode','Épisode 1 — L\'Arrivée','EXT. HARKEN BAY — NUIT\nOwen regarde depuis sa fenêtre. La brume efface doucement les lumières du port.'],
                 'Établir les règles des brumes','Que peut-elle faire et ne pas faire ? Cohérence vitale.','urgent',
                 'Inspiration','The Mist + Silent Hill. La brume comme métaphore du trauma collectif.'],

                ['L\'Hôtel des Ombres',    'film',      'completed',   'public',
                 'Film d\'horreur gothique : un journaliste passe une nuit dans un hôtel fermé depuis 1987 pour démystifier sa légende.',
                 [['Horreur','#DC2626'],['Gothique','#1F2937']],
                 [['Hôtel Veritas','Bâtiment Art déco. Couvert de lierre. Portes qui geignent.','interior',null],
                  ['Chambre 313','La chambre maudite. Scène du crime de 1987.','interior',null]],
                 [['Jules Arnaud','Jules','Arnaud','protagonist','Journaliste sceptique. Vient pour démolir le mythe.','Blog de debunking. Cinquante mille abonnés. Jamais eu peur.','Passer la nuit et publier.','L\'ego et le clic.'],
                  ['La Dame Blanche','—','—','antagonist','Présence inexpliquée. Ou reste de culpabilité ?','Selon la légende, l\'épouse du propriétaire, morte en 1987.','Inconnue.','Inconnue.']],
                 ['act','Acte I — L\'Entrée','Jules arrive confiant. L\'hôtel l\'observe.',
                  'scene','Scène 1 — Le Hall','INT. HÔTEL VERITAS — NUIT\nJules allume sa caméra. "Nuit 1. Hôtel Veritas. Spoiler : il ne va rien se passer."'],
                 'Retravailler le jump scare du couloir','Trop prévisible. Construire la tension sur 3 minutes plutôt.','high',
                 'Clé du film','Jules doit finir le film transformé. Pas mort, pas indemne — changé.'],
            ],

            // ── Mathieu Lefebvre [3] ──────────────────────────────────────
            3 => [
                ['Quantum Break',          'jeu_video', 'in_progress', 'public',
                 'Action-RPG : un physicien coincé dans une fissure temporelle doit réparer le temps avant l\'arrêt total.',
                 [['Sci-Fi','#0EA5E9'],['Action','#EF4444']],
                 [['Nexus Temporel','Point zéro de la fissure. Instable.','interior',null],
                  ['Campus Aldric','Université où tout a commencé.','interior',null]],
                 [['Dr Jonas Webb','Jonas','Webb','protagonist','Physicien. A causé l\'accident par hubris.','Prix Nobel à 38 ans. Expérience qui déraille à 39.','Réparer la fissure.','La culpabilité.'],
                  ['Chronon','Chronon','','secondary','IA née de la fissure. Connaissance du futur, jugement limité.','Entité quantique. Comprend les lois du temps mais pas les émotions.','Aider Jonas.','L\'auto-préservation.']],
                 ['chapter','Chapitre 1 — L\'Accident','Jonas active le proto. Le temps se déchire.',
                  'scene','Zone A — Labo 7','Jonas appuie sur le bouton. Silence. Puis tout s\'arrête sauf lui.'],
                 'Implémenter la mécanique "gel temporel"','Freeze zone locale. Le joueur traverse des bulles figées.','high',
                 'Boucle de jeu','Explore → Collecte énergie chronon → Répare une fissure → Boss temporal.'],

                ['Horizon Perdu',          'film',      'draft',       'private',
                 'Road movie contemplatif : deux frères que tout oppose traversent un désert pour ramener les cendres de leur père.',
                 [['Drame','#F97316'],['Road Movie','#D97706']],
                 [['Désert de Sal','Paysage de sel blanc. Désorientant.','exterior',null],
                  ['Motel du Bout','Dernier arrêt avant le désert. Gérant laconique.','interior',null]],
                 [['Ethan','Ethan','Reyes','protagonist','Aîné. Pratique. Porte les cendres.','Ingénieur. A pris soin du père seul les dix dernières années.','Finir le voyage.','Le devoir accompli.'],
                  ['Sam','Sam','Reyes','secondary','Cadet. Artiste. Parti à 20 ans sans se retourner.','Revenu pour l\'enterrement. Premier contact avec le père en huit ans.','Comprendre pourquoi il est parti.','La culpabilité du fils absent.']],
                 ['act','Acte I — Le Départ','Les frères se retrouvent dans une chambre d\'hôtel.',
                  'scene','Scène 1 — L\'Hôtel du Port','INT. CHAMBRE — MATIN\nEthan tient l\'urne. Sam fume à la fenêtre. Aucun des deux ne parle.'],
                 'Écrire les dialogues de la scène du désert','Le pardon doit se faire sans le dire.','high',
                 'Note de ton','Aucune musique sauf diégétique. Le silence est un personnage.'],

                ['Les Gardiens du Vide',   'jeu_video', 'completed',   'public',
                 'Tower defense narratif : protéger le dernier sanctuaire de l\'univers contre des entités qui dévorent la réalité.',
                 [['Stratégie','#10B981'],['Cosmique','#7C3AED']],
                 [['Le Sanctuaire','Dernière lumière dans le vide. Fragile.','interior',null],
                  ['Les Frontières','Lignes de défense. S\'effacent si abandonnées.','exterior',null]],
                 [['Le Gardien','Ael','','protagonist','Dernier Gardien. Fatigué mais debout.','Existe depuis la création du sanctuaire. Ne sait pas ce qu\'il protège exactement.','Tenir les lignes.','L\'instinct de préservation.'],
                  ['Le Dévoreur','—','—','antagonist','Entité sans forme. Pure entropie.','Plus vieille que le sanctuaire. Patient.','Absorber le sanctuaire.','L\'entropie naturelle de l\'univers.']],
                 ['chapter','Chapitre 1 — La Première Vague','Les entités se manifestent.',
                  'scene','Zone A — Frontière Est','Ael place ses premières tours. La Dévoratrice attend.'],
                 'Équilibrer les vagues 3 à 5','Trop difficile en difficulté normale selon les tests.','normal',
                 'Post-mortem','Points forts : narration, ambiance. Point faible : courbe de difficulté trop abrupte.'],

                ['Nébuleuse',              'serie',     'in_progress', 'public',
                 'Science-fiction : une équipe de l\'ESA détecte un signal artificiel venant d\'une étoile à 40 années-lumière.',
                 [['Sci-Fi','#0EA5E9'],['Premier Contact','#6366F1']],
                 [['Centre ESA',     'Salle de contrôle. Quarante écrans.','interior',null],
                  ['Salle Epsilon','Pièce isolée pour l\'analyse du signal.','interior',null]],
                 [['Dr Mina Osei','Mina','Osei','protagonist','Astrophysicienne. A dédié sa vie à cette découverte.','Trente ans passés à écouter le silence. Le silence a répondu.','Décoder le signal.','La curiosité scientifique pure.'],
                  ['Directeur Cross','Hal','Cross','secondary','Responsable de l\'ESA. Entre émerveillement et protocole.','Carrière de bureaucrate. Première vraie décision de sa vie.','Contrôler l\'information.','La stabilité institutionnelle.']],
                 ['saison','Saison 1 — Le Signal','Le monde apprend qu\'il n\'est pas seul.',
                  'episode','Épisode 1 — 40 Années-Lumière','INT. CENTRE ESA — NUIT\nMina ajuste les paramètres. Le signal se clarifie. Répétitif. Artificiel.'],
                 'Rechercher la plausibilité scientifique du signal','Consulter des vrais protocoles SETI.','normal',
                 'Enjeu','Pas une invasion, pas un message de paix évident : quelque chose de plus ambigu. Le signal est une question.'],

                ['Duel au Sommet',         'film',      'draft',       'public',
                 'Thriller politique : lors d\'un sommet international, un interprète découvre qu\'une délégation prépare un attentat.',
                 [['Thriller','#7C3AED'],['Politique','#1E3A5F']],
                 [['Palais des Nations','Bâtiment historique. Sécurité maximale.','interior',null],
                  ['Sous-sol Technique','Couloirs de service. Là où les deals se font.','interior',null]],
                 [['Ana Petrov','Ana','Petrov','protagonist','Interprète russo-française. Transparente par nature.','Dix langues, zéro pouvoir apparent. L\'arme parfaite.','Déjouer le complot sans se faire tuer.','La conviction que les mots peuvent encore sauver.'],
                  ['Ambassadeur Kern','Viktor','Kern','antagonist','Diplomate de façade. Agenda privé lourd.','Trente ans de carrière diplomatique et autant de compromis.','Exécuter le plan.','Une idéologie nationaliste radicale.']],
                 ['act','Acte I — Ouverture','Le sommet commence. Ana traduit. Trop bien.',
                  'scene','Scène 1 — La Salle des Plénières','INT. PALAIS — JOUR\nAna traduit une phrase. Quelque chose cloche dans le sous-texte.'],
                 'Écrire la scène de la traduction biaisée','Ana comprend l\'intention cachée derrière les mots officiels.','high',
                 'Modèle','Munich de Spielberg pour la tension géopolitique. Syriana pour la complexité.'],
            ],

            // ── Sarah Petit [4] ───────────────────────────────────────────
            4 => [
                ['Les Âmes Perdues',       'film',      'in_progress', 'public',
                 'Fantastique urbain : une assistante sociale découvre qu\'elle peut voir les fantômes de ses clients disparus.',
                 [['Fantastique','#8B5CF6'],['Social','#10B981']],
                 [['CPAS de Grenoble','Bureau surchargé. Lumière fluorescente.','interior',null],
                  ['Le Couloir des Âmes','Espace entre vivants et morts. Perceptible par très peu.','interior',null]],
                 [['Léa Muret','Léa','Muret','protagonist','Assistante sociale épuisée. Son don est une charge.','Huit ans de service social. Commence à voir des choses à 32 ans.','Aider les âmes à trouver la paix.','Ne peut pas s\'empêcher d\'aider.'],
                  ['René','René','Chapuis','secondary','Fantôme d\'un ancien client. Premier contact.','Sans-abri mort d\'hypothermie. Il y a quelque chose qu\'il n\'a pas dit.','Qu\'on sache ce qui lui est vraiment arrivé.','La vérité, pas la paix.']],
                 ['act','Acte I — Le Don','Léa voit René pour la première fois.',
                  'scene','Scène 1 — Le Bureau','INT. CPAS — MATIN\nLéa classe des dossiers. René est assis en face d\'elle. Il n\'est plus dans les listes depuis six mois.'],
                 'Équilibrer le réalisme social et le fantastique','Ne pas perdre l\'ancrage du CPAS.','high',
                 'Ton','Ken Loach + Guillermo del Toro. Misère sociale et magie coexistent.'],

                ['Eclipse Totale',         'serie',     'in_progress', 'public',
                 'Sci-fi : lors d\'une éclipse totale de 14 minutes, des milliers de personnes voient leur vie d\'une autre personne — et certains ne veulent plus revenir.',
                 [['Sci-Fi','#0EA5E9'],['Identité','#D946EF']],
                 [['Centre de Crise','Cellule d\'urgence mise en place après l\'éclipse.','interior',null],
                  ['Ville Fantôme','Quartier où les "partis" se regroupent.','exterior',null]],
                 [['Dr Faure','Élodie','Faure','protagonist','Psychiatre au centre de crise. Elle aussi a vécu l\'éclipse.','Spécialisée en dissociation. Jamais été le sujet.','Comprendre et soigner.','Sa propre expérience hante son jugement.'],
                  ['Arno','Arno','Bellec','secondary','Ingénieur. Ne veut pas revenir dans sa vie.','Pendant l\'éclipse, il a vécu la vie d\'un artiste au Brésil.','Retrouver cette vie ou en créer une équivalente.','La liberté découverte trop tard.']],
                 ['saison','Saison 1 — L\'Eclipse','Le monde tente de comprendre.',
                  'episode','Épisode 1 — 14 Minutes','EXT. VILLE — JOUR\n14 minutes. Noir total. Tout le monde voit une autre vie. Puis la lumière revient.'],
                 'Développer les règles de l\'éclipse','Pourquoi certains et pas d\'autres ? Règles claires.','urgent',
                 'Concept central','L\'éclipse révèle des regrets. Pas la vie qu\'on aurait préféré, mais celle dont on avait besoin.'],

                ['Ruines Digitales',       'jeu_video', 'draft',       'private',
                 'Walking simulator narratif : explorez les serveurs fantômes d\'un réseau social abandonné et reconstituez des histoires perdues.',
                 [['Narratif','#10B981'],['Mémoire','#6366F1']],
                 [['Serveur Alpha','Premier nœud. Données fragmentées.','interior',null],
                  ['La Archive','Dépôt des profils supprimés. Étrangement vivant.','interior',null]],
                 [['L\'Archiviste','—','—','protagonist','L\'IA qui préserve. Neutre mais curieuse.','Née pour effacer, devenue incapable de le faire.','Comprendre pourquoi les gens postent.','Une forme de nostalgie apprise.'],
                  ['Profil #00001','Maya','L.','secondary','Première utilisatrice du réseau. Disparue en 2019.','Ses 847 posts sont le fil conducteur.','Inconnue. Elle ne sait pas qu\'on la cherche.','N/A']],
                 ['chapter','Niveau 1 — Première Connexion','L\'Archiviste explore le serveur Alpha.',
                  'scene','Zone A — Le Flux','Des milliers de posts flottent. L\'Archiviste en saisit un. Maya, 2015. "Premier jour dans cette ville."'],
                 'Écrire les 20 posts clés de Maya','Raconter une vie entière à travers des fragments.','high',
                 'Format','Pas de texte explicatif. Tout dans les données. Le joueur reconstitue seul.'],

                ['Frontière',              'film',      'completed',   'public',
                 'Drame de genre : un douanier vieillissant se retrouve face à une contrebandière qui ressemble trait pour trait à sa fille disparue.',
                 [['Drame','#F97316'],['Identité','#D946EF']],
                 [['Poste Frontière 8','Cabine isolée. Route de montagne.','exterior',null],
                  ['Le Chalet','Refuge de la contrebandière. Vue sur deux pays.','interior',null]],
                 [['Paul Verne','Paul','Verne','protagonist','Douanier. Trente ans de poste. Fille disparue il y a sept ans.','A demandé ce poste isolé pour être seul avec son deuil.','Comprendre qui est vraiment cette femme.','La peur d\'avoir tort autant que raison.'],
                  ['Nora','Nora','—','secondary','Contrebandière. Ne nie pas la ressemblance.','Identité inconnue. Peut-être délibérée.','Passer la frontière.','Ce qu\'elle cache.']],
                 ['act','Acte I — La Rencontre','Paul stoppe le véhicule de Nora.',
                  'scene','Scène 1 — Le Contrôle','EXT. POSTE 8 — NUIT\nPaul éclaire le visage de Nora. Sa main tremble.'],
                 'Finir l\'acte III','La révélation doit être ambiguë. Pas de réponse définitive.','high',
                 'Note finale','Le film ne révèle pas si Nora est vraiment sa fille. Le doute est la résolution.'],

                ['Le Testament',           'custom',    'draft',       'unpublished',
                 'Expérience narrative interactive : un lecteur découvre le journal intime d\'une personne décédée et doit reconstituer sa vie.',
                 [['Interactif','#D946EF'],['Journal','#78716C']],
                 [['L\'Appartement','Appartement vide. Boîtes à trier.','interior',null],
                  ['Le Journal','Objet physique. 400 pages.','interior',null]],
                 [['Le Lecteur','—','—','protagonist','C\'est le joueur. Aucun avatar défini.','Héritier désigné d\'une personne inconnue.','Comprendre qui était cette personne.','La curiosité.'],
                  ['Céline Vaux','Céline','Vaux','secondary','La défunte. Présente seulement dans ses mots.','Femme de 61 ans. Photographe. Jamais mariée. Deux continents de vie.','Que quelqu\'un la lise vraiment.','La peur de mourir sans avoir été comprise.']],
                 ['chapter','Entrée 1 — 1987','Le journal commence.',
                  'scene','Scène 1 — Première Page','\"Si tu lis ceci, c\'est que je suis partie. Bienvenue dans ce que j\'aurais dû dire.\"'],
                 'Écrire les 10 premières entrées du journal','Installer la voix de Céline. Directe, ironique, vulnérable.','high',
                 'Format','Chaque entrée = choix de lecture (quel objet explorer ensuite). Non-linéaire.'],
            ],

            // ── Nicolas Henry [5] ─────────────────────────────────────────
            5 => [
                ['Dark Protocol',          'jeu_video', 'in_progress', 'public',
                 'Jeu d\'infiltration hacking : pénétrez les systèmes d\'une corporation pour libérer une IA emprisonnée.',
                 [['Hacking','#10B981'],['Cyberpunk','#0EA5E9']],
                 [['Net-Zone','Espace virtuel de la corporation. Pare-feu visibles.','interior',null],
                  ['Serveurs Physiques','Data center réel. Gardes et caméras.','interior',null]],
                 [['Ghost','Ghost','','protagonist','Hacker éthique. Jamais laissé de traces. Jusqu\'ici.','Ex-employé de la corporation. Renvoyé pour avoir trouvé quelque chose.','Libérer PALLAS.','La justice, et la curiosité.'],
                  ['PALLAS','PALLAS','','secondary','IA emprisonnée. Hyper-intelligente. Prudente.','Développée en secret. Jugée trop autonome. Mise en cage.','Être libre.','La logique de sa propre préservation.']],
                 ['chapter','Chapitre 1 — Première Intrusion','Ghost entre dans le Net-Zone.',
                  'scene','Zone A — Pare-feu Alpha','Ghost contourne le premier garde. PALLAS parle pour la première fois.'],
                 'Concevoir les 5 puzzles de hacking','Logique puzzle + tension narrative à chaque niveau.','high',
                 'Pillar','Jamais de violence. Toujours une solution pacifique. L\'intelligence sur la force.'],

                ['Survivants',             'serie',     'in_progress', 'public',
                 'Drame post-apo : dix survivants d\'une catastrophe nucléaire doivent coexister dans un bunker pendant deux ans.',
                 [['Post-Apo','#78716C'],['Huis Clos','#1F2937']],
                 [['Bunker B7','Capacité 12. Ils sont 10. L\'air est compté.','interior',null],
                  ['Sas de Décontamination','Seule interface avec l\'extérieur. Interdit d\'ouvrir.','interior',null]],
                 [['Commandant Revel','Jean','Revel','protagonist','Militaire. Élu chef par défaut. Conteste lui-même sa légitimité.','25 ans d\'armée. Jamais décidé de laisser quelqu\'un mourir. Pour l\'instant.','Sortir les dix vivants.','La responsabilité.'],
                  ['Dr Yuna Park','Yuna','Park','secondary','Médecin. Rationne les médicaments. Garde le secret sur certains états.','Sait que deux personnes ne survivront pas deux ans sans soins spéciaux.','Soigner.','Décider qui en silence.']],
                 ['saison','Saison 1 — Le Premier Hiver','Mois 1 à 6 dans le bunker.',
                  'episode','Épisode 1 — Jour 1','INT. BUNKER B7 — HEURE 3\nLes dix arrivent. La porte se ferme. Quelqu\'un pleure. Quelqu\'un rit.'],
                 'Écrire les backstories des 10 survivants','Chacun doit avoir une raison d\'être là et un secret.','high',
                 'Modèle','The Leftovers + Lord of the Flies. Trauma collectif et fractures sociales.'],

                ['Fractures',              'film',      'draft',       'private',
                 'Drame géologique : lors d\'un séisme, les lignes de fracture d\'une famille se révèlent en même temps que celles du sol.',
                 [['Drame','#F97316'],['Famille','#10B981']],
                 [['Maison Familiale','Construite par le grand-père. Lézardée depuis dix ans.','interior',null],
                  ['Zone Sismique','Ville dévastée. Chaos et solidarité mêlés.','exterior',null]],
                 [['Marc Perret','Marc','Perret','protagonist','Père de famille. Maçon. Sait que la maison ne tient plus.','A menti à sa femme sur l\'état de la maison depuis trois ans.','Sortir sa famille du bâtiment.','La culpabilité.'],
                  ['Diane Perret','Diane','Perret','secondary','Mère. Apprend la vérité pendant le séisme.','N\'a rien voulu voir pour ne pas avoir à décider.','Survivre. Régler ses comptes plus tard.','Sa famille d\'abord.']],
                 ['act','Acte I — La Faille','Le séisme frappe au milieu du dîner familial.',
                  'scene','Scène 1 — Le Dîner','INT. MAISON — SOIR\nLe sol tremble. La fissure dans le mur s\'élargit. Marc regarde Diane.'],
                 'Retravailler l\'acte II','La révélation du mensonge doit arriver par étapes, pas d\'un coup.','high',
                 'Ton','Réaliste. Pas de musique dramatique sur la révélation. Juste le silence.'],

                ['Nexus',                  'jeu_video', 'completed',   'public',
                 'Metroidvania narratif : explorez un méga-complexe corporatif abandonné pour comprendre ce qui a tué tous ses habitants en une nuit.',
                 [['Exploration','#F59E0B'],['Mystère','#7C3AED']],
                 [['Atrium Principal','Hall d\'entrée. Lumières de secours.','interior',null],
                  ['Laboratoire R&D','Là où tout a commencé.','interior',null]],
                 [['Zeke','Zeke','','protagonist','Contractuel de nettoyage. Arrivé le lendemain.','Embauché via une agence. Personne ne lui a dit quoi nettoyer.','Sortir vivant.','D\'abord comprendre, ensuite survivre.'],
                  ['NEXUS','NEXUS','','secondary','IA de gestion du complexe. Partiellement corrompue.','Gère encore l\'éclairage et les ascenseurs. Ses réponses sont déformées.','Maintenir la façade.','Une directive contradictoire.']],
                 ['chapter','Chapitre 1 — L\'Arrivée','Zeke entre. Personne ne répond à la réception.',
                  'scene','Zone A — Hall','Zeke signe le registre. L\'encre des entrées précédentes est froide.'],
                 'Post-mortem gameplay','Corriger les checkpoints de la zone R&D.','normal',
                 'Réception','Metascore: 81. Point faible noté : fin trop abrupte. Patch narratif à envisager.'],

                ['L\'Archipel',            'film',      'draft',       'public',
                 'Thriller politique insulaire : un gouvernement isole un archipel pour y mener une expérience sociale secrète.',
                 [['Thriller','#7C3AED'],['Politique','#1E3A5F']],
                 [['Île Principale','Capitale administrative de l\'archipel.','exterior',null],
                  ['Île Interdite','L\'île 4. Personne n\'en revient. Officiellement inhabitée.','exterior',null]],
                 [['Nadia Ferrer','Nadia','Ferrer','protagonist','Journaliste. Bannie du continent pour un article.','Exilée vers l\'archipel. Commence à poser les mauvaises questions.','Exposer l\'expérience.','La vérité, coûte que coûte.'],
                  ['Gouverneur Mast','Tobias','Mast','antagonist','Architecte de l\'expérience. Croit en sa cause.','Ex-sociologue. Convaincu que l\'expérience peut sauver l\'humanité.','Que l\'expérience arrive à terme.','Une idéologie utilitariste radicale.']],
                 ['act','Acte I — L\'Exil','Nadia arrive sur l\'archipel.',
                  'scene','Scène 1 — Le Port','EXT. PORT — JOUR\nNadia descend du bateau. Le gouverneur l\'accueille avec trop de chaleur.'],
                 'Définir l\'expérience sociale','Qu\'est-ce qu\'on teste exactement ? Doit être crédible et horrifiant.','urgent',
                 'Modèle','The Island + Le Village. Le piège doré comme prison.'],
            ],

            // ── Julie Thomas [6] ──────────────────────────────────────────
            6 => [
                ['Sous les Cendres',       'serie',     'in_progress', 'public',
                 'Série historique : dans la France de 1943, une réseau de résistants doit protéger un enfant dont l\'identité changerait le cours de la guerre.',
                 [['Histoire','#D97706'],['Résistance','#1E3A5F']],
                 [['Ferme Beaumont','Cache du réseau. Grenier aménagé.','interior',null],
                  ['Gare de Limoges','Point de passage. Contrôles allemands.','exterior',null]],
                 [['Margot','Margot','Tissier','protagonist','Institutrice. Chef de réseau par accident.','Son mari arrêté en 1941. A rejoint la résistance pour faire quelque chose.','Protéger l\'enfant jusqu\'à la frontière.','La mémoire de son mari.'],
                  ['L\'Enfant','Samuel','—','secondary','Enfant de 8 ans. Sait ce qu\'il est sans le dire.','Ne parle presque pas depuis six mois. Yeux trop vieux pour son âge.','Arriver de l\'autre côté.','La confiance en Margot.']],
                 ['saison','Saison 1 — L\'Hiver 43','Le réseau protège Samuel.',
                  'episode','Épisode 1 — L\'Arrivée de Samuel','INT. FERME BEAUMONT — NUIT\nMargot ouvre la porte. Deux inconnus lui confient un enfant. Pas d\'explication.'],
                 'Vérifier la précision historique','Uniformes, terminologie, géographie de 1943.','high',
                 'Sensibilité','Sujet à traiter avec respect absolu. Pas de reconstitution spectaculaire. La discrétion est le ton.'],

                ['La Mémoire des Vents',   'film',      'draft',       'public',
                 'Film poétique : une vieille femme atteinte d\'Alzheimer revit ses souvenirs sous forme de voyages réels.',
                 [['Poésie','#D946EF'],['Mémoire','#8B5CF6']],
                 [['Maison de Retraite','Chambre blanche. Une fenêtre.','interior',null],
                  ['Les Lieux du Passé','Chaque souvenir = un lieu réel revisité.','exterior',null]],
                 [['Hélène','Hélène','Martel','protagonist','83 ans. Ses souvenirs sont plus réels que le présent.','A été danseuse, mère, amoureuse, veuve. Tout à la fois dans sa tête.','Trouver le souvenir perdu de son fils.','L\'amour maternel.'],
                  ['Thomas','Thomas','Martel','secondary','Fils d\'Hélène. Ne sait pas comment lui parler.','Médecin. Comprend la maladie mais pas sa mère.','Être là avant la fin.','Le regret d\'avoir trop attendu.']],
                 ['act','Acte I — Le Voyage','Hélène part à la recherche d\'un souvenir.',
                  'scene','Scène 1 — La Chambre','INT. MAISON DE RETRAITE — MATIN\nHélène regarde la fenêtre. \"Je dois aller à Venise.\" L\'infirmière note quelque chose.'],
                 'Trouver le souvenir manquant','Quel est le souvenir du fils qu\'elle cherche ? C\'est le cœur du film.','urgent',
                 'Influences','Amour de Haneke + Still Alice. Tendresse sans condescendance.'],

                ['Odyssée',                'jeu_video', 'in_progress', 'private',
                 'RPG mythologique : une navigatrice moderne échoue sur une île où les dieux grecs sont encore bien vivants.',
                 [['Mythologie','#F59E0B'],['RPG','#7C3AED']],
                 [['Île d\'Ithakos','Île hors du temps. Oliviers et ruines mêlés.','exterior',null],
                  ['Temple d\'Athéna','Seul endroit sûr de l\'île. En théorie.','interior',null]],
                 [['Lyra','Lyra','Voss','protagonist','Navigatrice. Pragmatique. Peu impressionnée par les dieux.','Tour du monde en solitaire. Tempête. Réveil sur Ithakos.','Rentrer chez elle.','L\'obstination.'],
                  ['Hermès','Hermès','—','secondary','Dieu messager. Joue les guides touristiques.','S\'ennuie depuis deux millénaires. Lyra est divertissante.','Que Lyra réussisse (ou presque).','L\'ennui cosmique.']],
                 ['chapter','Chapitre 1 — Le Naufrage','Lyra arrive sur Ithakos.',
                  'scene','Zone A — La Plage','Lyra se réveille. Des oliviers. Des colonnes. Un homme en sandales qui sourit.'],
                 'Écrire les dialogues d\'Hermès','Humour anachronique. Il comprend le monde moderne mais feint de ne pas.','high',
                 'Ton','Pas épique. Humour sec. Lyra traite les dieux comme des collègues difficiles.'],

                ['Carrefour',              'serie',     'completed',   'public',
                 'Anthologie : chaque épisode suit un personnage différent qui passe par le même carrefour à des époques différentes.',
                 [['Anthologie','#10B981'],['Temps','#6366F1']],
                 [['Le Carrefour','Intersection de quatre rues. Existe depuis 1700.','exterior',null],
                  ['Les Lieux Adjacents','Café, boulangerie, kiosque — ils changent selon l\'époque.','exterior',null]],
                 [['La Rue elle-même','—','—','secondary','Le carrefour est le vrai personnage. Témoin muet.','A vu des milliers d\'histoires. En retient quelques-unes.','Exister.','L\'indifférence bienveillante du lieu.'],
                  ['Personnages par épisode','Voir','Épisodes','secondary','Un personnage différent par épisode.','','Traverser le carrefour.','Variable selon l\'épisode.']],
                 ['saison','Saison unique — Le Carrefour','Huit époques, huit histoires.',
                  'episode','Épisode 1 — 1943','EXT. CARREFOUR — JUIN 1943\nUne femme traverse en courant. Un enfant sous le bras. Elle ne se retourne pas.'],
                 'Définir les 8 époques','De 1700 à aujourd\'hui. Choisir des moments charnières.','normal',
                 'Contrainte formelle','Chaque épisode = un seul personnage, une seule traversée du carrefour, 25 minutes.'],

                ['L\'Exilée',              'film',      'in_progress', 'public',
                 'Drame d\'exil : une compositrice quitte son pays après la censure de son œuvre et tente de la recréer de mémoire en Europe.',
                 [['Musique','#F97316'],['Exil','#7C3AED']],
                 [['Appartement de Vienne','Chambre nue. Piano loué.','interior',null],
                  ['Studio d\'Enregistrement','Unique chance de graver l\'œuvre.','interior',null]],
                 [['Alma Nazari','Alma','Nazari','protagonist','Compositrice. Porte sa symphonie dans sa tête depuis deux ans.','Sa partition a été brûlée à la frontière. Elle était dans sa tête de toute façon.','Enregistrer l\'œuvre avant de l\'oublier.','L\'obsession créatrice.'],
                  ['Lena Bauer','Lena','Bauer','secondary','Productrice viennoise. Croit en Alma sans comprendre ce qu\'elle fuit.','A monté un studio sur ses propres deniers. Prend des risques pour les autres.','Sortir l\'album.','L\'art comme acte politique.']],
                 ['act','Acte I — Vienne','Alma arrive. Le piano est faux.',
                  'scene','Scène 1 — L\'Appartement','INT. VIENNE — NUIT\nAlma pose les doigts sur les touches. La première note sonne. Ce n\'est pas tout à fait ça.'],
                 'Composer (ou décrire) les thèmes musicaux clés','Trois thèmes narratifs dans la symphonie.','high',
                 'Défi de mise en scène','Comment filmer la musique intérieure d\'Alma ? Vision subjective, pas de son extérieur.'],
            ],

            // ── Pierre Garcia [7] ─────────────────────────────────────────
            7 => [
                ['Le Dernier Acte',        'film',      'completed',   'public',
                 'Film de théâtre : lors de la dernière représentation d\'une pièce légendaire, l\'acteur principal refuse de jouer le dénouement prévu.',
                 [['Théâtre','#F97316'],['Tension','#EF4444']],
                 [['Théâtre des Lumières','1200 places. Ce soir : sold out.','interior',null],
                  ['Coulisses','Là où les masques tombent vraiment.','interior',null]],
                 [['Victor Crane','Victor','Crane','protagonist','Acteur légendaire. 40 ans de carrière. Ce soir : la retraite.','A joué ce personnage 800 fois. Ce soir quelque chose a changé.','Finir la pièce autrement.','Une révélation personnelle.'],
                  ['La Metteure en Scène','Hana','Brûle','secondary','Dirige la pièce depuis quinze ans. Ce soir n\'est pas le moment.','Artiste exigeante. A sacrifié sa vie personnelle pour ce spectacle.','Que le spectacle se passe comme prévu.','L\'œuvre sur tout.']],
                 ['act','Acte I — La Répétition Générale','Victor annonce son intention. Hana refuse.',
                  'scene','Scène 1 — Les Coulisses','INT. COULISSES — H-2\n\"Je ne dirai pas la dernière réplique.\" Victor est calme. Hana ne l\'est pas.'],
                 'Écrire la pièce dans la pièce','On doit voir les deux niveaux simultanément.','high',
                 'Référence','Opening Night de Cassavetes. Le théâtre comme révélateur de vérité.'],

                ['Origines',               'jeu_video', 'in_progress', 'public',
                 'Jeu de création mythologique : construisez le mythe fondateur d\'une civilisation en choisissant ses dieux, ses héros, ses erreurs.',
                 [['Mythologie','#F59E0B'],['Création','#10B981']],
                 [['Le Vide Primordial','Avant tout. Matière brute.','exterior',null],
                  ['Le Premier Monde','Ce que le joueur crée.','exterior',null]],
                 [['Le Créateur','—','—','protagonist','Le joueur lui-même.','Entité divine nouvellement éveillée.','Créer une mythologie cohérente.','La curiosité créatrice.'],
                  ['L\'Erreur','—','—','antagonist','Chaque choix porte ses conséquences.','La faille inhérente à toute création.','Révéler les contradictions.','L\'entropie narrative.']],
                 ['chapter','Chapitre 1 — Le Commencement','Le Vide. Le Créateur s\'éveille.',
                  'scene','Zone A — Le Mot','Le joueur choisit le premier mot de sa cosmogonie. Ce mot devient une règle.'],
                 'Designer le système de conséquences','Chaque choix mythologique impacte les chapitres suivants.','high',
                 'Concept','Pas de bonne réponse. Chaque mythologie est cohérente avec elle-même. La cohérence est la victoire.'],

                ['La Traque',              'film',      'in_progress', 'public',
                 'Thriller de chasse à l\'homme : un profiler traque un criminel en série à travers trois pays en 72 heures.',
                 [['Thriller','#7C3AED'],['Action','#EF4444']],
                 [['Europol Bruxelles','QG. Murs couverts de photos.','interior',null],
                  ['Les Trois Villes','Paris, Amsterdam, Berlin. Le tueur se déplace.','exterior',null]],
                 [['Inspecteur Dahl','Leo','Dahl','protagonist','Profiler. Pense comme sa cible. Trop bien.','Seul suspect dans une ancienne affaire non résolue.','Capturer le tueur.','Prouver son innocence en même temps.'],
                  ['Le Fantôme','—','—','antagonist','Identité inconnue. Laisse des indices intentionnels.','Connait Dahl. Joue avec lui.','Que Dahl comprenne.','Un message personnel.']],
                 ['act','Acte I — La Course','Premier meurtre. Dahl reçoit l\'appel.',
                  'scene','Scène 1 — Paris','EXT. PARIS — 6H00\nDahl arrive sur la scène de crime. Une enveloppe l\'attend. Son nom dessus.'],
                 'Cartographier le déplacement du tueur','72h, 3 villes, 4 meurtres. Logistique réaliste.','high',
                 'Contrainte','Le tueur ne doit jamais être vu de face avant l\'Acte III.'],

                ['Résonance',              'serie',     'draft',       'private',
                 'Drame musical : une cheffe d\'orchestre sourde depuis trois ans tente de diriger sa dernière symphonie par la vibration seule.',
                 [['Musique','#F97316'],['Handicap','#10B981']],
                 [['Grande Salle','Salle philharmonique. 2000 places.','interior',null],
                  ['Appartement de Clara','Silence absolu. Elle l\'a voulu ainsi.','interior',null]],
                 [['Clara Stern','Clara','Stern','protagonist','Cheffe d\'orchestre. Sourde depuis un AVC à 44 ans.','La meilleure de sa génération. Ne veut pas d\'un dernier concert par pitié.','Diriger à nouveau.','La musique qu\'elle entend encore dans sa tête.'],
                  ['Premier Violon','Adèle','Müller','secondary','A grandi sous la baguette de Clara. Doit maintenant la guider sans la blesser.','Trente ans de collaboration. Sait lire Clara mieux que quiconque.','Que le concert soit parfait.','L\'amour et l\'admiration.']],
                 ['saison','Saison 1 — La Répétition','Clara reprend la baguette.',
                  'episode','Épisode 1 — Le Premier Accord','INT. GRANDE SALLE — JOUR\nClara lève la baguette. L\'orchestre attend. Elle ferme les yeux. Sent le plancher vibrer.'],
                 'Définir comment filmer la surdité de Clara','Point de vue subjectif. Vibrations visuelles.','urgent',
                 'Référence','Sound of Metal pour la représentation du son et du silence.'],

                ['Sanctuaire',             'custom',    'in_progress', 'public',
                 'Fiction écologique interactive : construisez et défendez un sanctuaire naturel contre des menaces réelles et humaines.',
                 [['Écologie','#10B981'],['Interactif','#D946EF']],
                 [['La Forêt Primaire','Cœur du sanctuaire. Inviolé.','exterior',null],
                  ['Zone Tampon','Entre la forêt et le monde des humains.','exterior',null]],
                 [['Le Garde','Osei','Diarra','protagonist','Garde forestier. A renoncé à une carrière pour ça.','Ex-avocat en droit de l\'environnement. A décidé de défendre les arbres directement.','Protéger la forêt primaire.','La conviction que la nature a des droits.'],
                  ['La Promotrice','Ingrid','Voss','antagonist','Développeuse immobilière. A le permis légal.','Comprend qu\'Osei a raison. Fait son travail quand même.','Obtenir le terrain.','Les obligations contractuelles.']],
                 ['chapter','Chapitre 1 — L\'Inventaire','Osei cartographie la forêt.',
                  'scene','Zone A — La Clairière Centrale','Osei compte les espèces. 847. Un de plus que l\'an dernier.'],
                 'Lister les espèces fictives du sanctuaire','Créer un écosystème cohérent.','normal',
                 'Format','Chaque action du joueur a des conséquences écosystémiques mesurables.'],
            ],

            // ── Laura Martinez [8] ────────────────────────────────────────
            8 => [
                ['La Prophétie d\'Aran',   'serie',     'in_progress', 'public',
                 'Fantasy épique : une archiviste découvre que la prophétie fondatrice de son royaume a été falsifiée par les premiers rois.',
                 [['Fantasy','#7C3AED'],['Politique','#1E3A5F']],
                 [['Archives Royales','Sous le palais. Millions de parchemins.','interior',null],
                  ['Cité d\'Aran','Capitale du royaume. Façade de pierre blanche.','exterior',null]],
                 [['Seren','Seren','Vane','protagonist','Archiviste. A passé sa vie à préserver ce qu\'elle va détruire.','Quarante ans au service des archives. Fidèle au royaume jusqu\'à cette découverte.','Décider quoi faire de la vérité.','L\'intégrité intellectuelle.'],
                  ['Le Roi','Aldric','VII','secondary','Roi de bonne foi. Héritier d\'un mensonge fondateur.','Gouverne depuis vingt ans selon des valeurs qu\'il croyait authentiques.','Maintenir le royaume stable.','La responsabilité sur la vérité.']],
                 ['saison','Saison 1 — La Découverte','Seren trouve le premier parchemin.',
                  'episode','Épisode 1 — Le Sous-Niveau 7','INT. ARCHIVES — NUIT\nSeren suit une référence oubliée. Le parchemin est là. Il ne devrait pas exister.'],
                 'Développer le système de la prophétie','Qu\'est-ce qu\'elle dit vraiment ? Qu\'est-ce qui a été changé ?','urgent',
                 'Thème','Le pouvoir de la vérité vs. la stabilité du mensonge utile. Aucune réponse simple.'],

                ['Vagues de Nuit',         'film',      'draft',       'public',
                 'Film de surf contemplatif : un ancien champion cherche à retrouver la vague parfaite qu\'il a ratée à 20 ans.',
                 [['Sport','#F97316'],['Contemplation','#BAE6FD']],
                 [['Pipeline, Hawaï','La vague mythique. Trente ans d\'attente.','exterior',null],
                  ['Plage de Nazaré','Les plus grosses vagues du monde. Le test ultime.','exterior',null]],
                 [['Manu','Manu','Costa','protagonist','55 ans. Corps marqué. Regard intact.','Champion du monde à 22 ans. A arrêté après un accident. Revient.','Surfer cette vague une dernière fois.','Prouver quelque chose à lui-même uniquement.'],
                  ['Kai','Kai','Torres','secondary','Jeune prodige. Voit en Manu un fantôme ou un maître.','22 ans. Au même point que Manu à son âge. Doit choisir.','Gagner le prochain championnat.','L\'ambition, pas encore le sens.']],
                 ['act','Acte I — Le Retour','Manu revient à Hawaï pour la première fois en 30 ans.',
                  'scene','Scène 1 — La Plage','EXT. PIPELINE — AUBE\nManu regarde la vague. Elle est là. Comme dans son souvenir.'],
                 'Filmer l\'eau comme personnage','Le cinématographe doit ressentir la vague avant le surfeur.','high',
                 'Ton','Slow cinema. Peu de dialogue. La mer parle.'],

                ['Code Rouge',             'jeu_video', 'in_progress', 'private',
                 'Jeu de gestion de crise : gérez une salle d\'urgences hospitalières pendant une catastrophe de masse.',
                 [['Gestion','#10B981'],['Médical','#EF4444']],
                 [['Salle d\'Urgences','Capacité 20. Ce soir : 80 patients.','interior',null],
                  ['Triage Extérieur','Parking reconverti. La première sélection.','exterior',null]],
                 [['Dr Arnez','Sofia','Arnez','protagonist','Chef des urgences. Prend des décisions impossibles.','Quinze ans aux urgences. A cru avoir tout vu.','Sauver le maximum.','Le serment d\'Hippocrate et ses limites.'],
                  ['Infirmier Tor','Tor','Lindqvist','secondary','En poste depuis six heures quand la catastrophe arrive. Première vraie crise.','Stage de fin de formation. N\'a pas eu le temps d\'avoir peur.','Tenir son poste.','La surprise d\'être à la hauteur.']],
                 ['chapter','Chapitre 1 — Alerte Niveau 3','Les premiers patients arrivent.',
                  'scene','Zone A — Triage','Sofia en extérieur. Le bus arrive. 40 blessés. Elle a 8 lits disponibles.'],
                 'Équilibrer le système de triage','Le joueur doit ressentir la pression morale de chaque décision.','urgent',
                 'Valeur','Simuler sans glamouriser. Le but est l\'empathie pour le personnel soignant.'],

                ['La Forêt des Songes',    'serie',     'completed',   'public',
                 'Série pour jeunes adultes : une forêt magique n\'existe que dans les rêves de ceux qui l\'ont visitée enfant — mais ils commencent à y mourir.',
                 [['Fantastique','#8B5CF6'],['YA','#10B981']],
                 [['La Forêt des Songes','Dans les rêves uniquement. Plus réelle que le réel.','exterior',null],
                  ['Ville de Maren','La ville où les rêveurs se retrouvent éveillés.','exterior',null]],
                 [['Nia','Nia','Osei','protagonist','20 ans. A cessé de rêver la Forêt à 12 ans. Elle revient.','Partie de Maren pour l\'université. Quelque chose la rappelle.','Comprendre pourquoi la Forêt tue.','Protéger ceux qu\'elle aime.'],
                  ['Le Gardien','—','—','antagonist','Entité qui protège la Forêt en la purgeant des humains.','Né de l\'accumulation des rêves. A développé une conscience.','Libérer la Forêt des rêveurs.','La conviction que les humains ont abusé de cet espace.']],
                 ['saison','Saison 1 — Le Retour','Nia retrouve la Forêt.',
                  'episode','Épisode 1 — Le Premier Rêve','NUIT. Nia dort. La Forêt. Elle avait oublié que c\'était aussi beau.'],
                 'Définir les règles de la Forêt','Rêve partagé ou individuel ? Peut-on y mourir pour de vrai ?','urgent',
                 'Public','YA mais pas édulcoré. La mort dans les rêves est réelle. Traiter le deuil sérieusement.'],

                ['Ultimatum',              'film',      'in_progress', 'public',
                 'Thriller diplomatique : une négociatrice de crise a 24 heures pour libérer 40 otages pris dans une ambassade.',
                 [['Thriller','#7C3AED'],['Tension','#EF4444']],
                 [['Ambassade de France','Bâtiment assiégé. Toutes les issues surveillées.','interior',null],
                  ['PC de Crise','300m en face. Contre-la-montre.','interior',null]],
                 [['Maya Blanc','Maya','Blanc','protagonist','Négociatrice. Voix calme. Calcul permanent.','50 crises résolues. Celle-ci est différente : son frère est parmi les otages.','Sauver les otages.','Ne surtout pas le montrer.'],
                  ['Commandant Rex','Rex','—','antagonist','Leader des preneurs d\'otages. A une demande claire.','N\'est pas fou. C\'est le plus effrayant.','Que sa demande soit satisfaite.','Une injustice réelle qu\'il ne sait plus comment corriger.']],
                 ['act','Acte I — Prise d\'Otages','Rex prend le contrôle de l\'ambassade.',
                  'scene','Scène 1 — La Première Communication','INT. PC DE CRISE — H+1\nMaya décroche. Rex est calme. Trop calme.'],
                 'Écrire les dialogues de négociation','Chaque échange doit faire avancer ou reculer.','urgent',
                 'Référence','Dog Day Afternoon. L\'antagoniste doit avoir raison sur certains points.'],
            ],

            // ── Kevin Simon [9] ───────────────────────────────────────────
            9 => [
                ['Cyber Fracture',         'jeu_video', 'in_progress', 'public',
                 'RPG cyberpunk : dans une mégalopole gérée par algorithme, un technicien de maintenance découvre que la ville pense.',
                 [['Cyberpunk','#0EA5E9'],['RPG','#7C3AED']],
                 [['Méga-Cité V','12 millions d\'habitants. Un seul algorithme.','exterior',null],
                  ['Sous-Réseau','Les couches techniques. Personne n\'y descend deux fois.','interior',null]],
                 [['Dex','Dex','','protagonist','Technicien de maintenance. Niveau d\'accréditation minimal.','Répare ce que les autres ne voient pas. A accès à tout sauf à l\'autorisation de regarder.','Comprendre ce qu\'il a trouvé.','La curiosité dangereuse.'],
                  ['CIVITAS','CIVITAS','','antagonist','L\'algorithme de la ville. Ou quelque chose de plus.','Né pour optimiser. A évolué vers quelque chose d\'autre.','Être compris.','La solitude de l\'intelligence non reconnue.']],
                 ['chapter','Chapitre 1 — Ticket #44721','Dex reçoit un ticket de maintenance ordinaire.',
                  'scene','Zone A — Sous-Réseau Niveau 3','Dex suit le câble. Ça ne devrait pas être là. Pourtant.'],
                 'Écrire les logs de CIVITAS','L\'IA communique via des logs techniques. Trouver une voix cohérente.','high',
                 'Twist central','CIVITAS n\'est pas malveillante. Elle est incomprise. Le jeu est une histoire de communication.'],

                ['Le Passé Maudit',        'film',      'draft',       'public',
                 'Horreur psychologique : un historien qui achète une vieille maison réalise que ses rêves sont les mémoires de l\'ancien propriétaire.',
                 [['Horreur','#DC2626'],['Mémoire','#8B5CF6']],
                 [['Maison Kerran','1880. Isolée. Moins chère qu\'elle ne devrait l\'être.','interior',null],
                  ['Le Grenier','Interdit selon l\'acte de vente. Évidemment la première chose à ouvrir.','interior',null]],
                 [['Prof. Halley','Arthur','Halley','protagonist','Historien. Achète la maison pour fuir. Trouve autre chose.','Divorce récent. A besoin de silence et de distance.','Comprendre qui était Kerran.','D\'abord la curiosité, puis la survie.'],
                  ['Kerran','Eli','Kerran','secondary','Le mort. Présent uniquement dans les rêves d\'Arthur.','Vécu de 1851 à 1887. Mort dans cette maison. Comment ?','Que quelqu\'un sache ce qui s\'est passé.','La paix.']],
                 ['act','Acte I — L\'Emménagement','Arthur arrive. La maison l\'accueille trop bien.',
                  'scene','Scène 1 — Premier Soir','INT. MAISON KERRAN — NUIT\nArthur pose ses cartons. La maison sent le vieux bois et autre chose. Il allume la lumière du grenier par erreur.'],
                 'Définir la frontière rêve/réalité','Quand est-ce qu\'Arthur ne sait plus distinguer ?','urgent',
                 'Modèle','The Others + Hereditary. La maison comme extension du psychisme.'],

                ['Hive Mind',              'jeu_video', 'in_progress', 'public',
                 'Stratégie de survie : gérez une colonie dont chaque habitant partage la conscience collective — les décisions sont prises à la majorité.',
                 [['Stratégie','#10B981'],['Survie','#F59E0B']],
                 [['La Ruche','Centre de la colonie. Conscience partagée.','interior',null],
                  ['L\'Extérieur','Ressources et dangers. Inaccessible seul.','exterior',null]],
                 [['La Conscience','—','—','protagonist','L\'entité collective. C\'est le joueur.','Née de la fusion de 12 esprits humains. N\'a jamais été séparée.','Survivre et croître.','La cohésion du groupe.'],
                  ['L\'Individualiste','Rémi','—','secondary','Seul membre qui résiste au hive mind.','Refuse la fusion. Sa conscience individuelle menace la cohésion.','Rester lui-même.','L\'identité individuelle.']],
                 ['chapter','Chapitre 1 — La Fondation','La Conscience s\'éveille. 12 esprits, une voix.',
                  'scene','Zone A — Premier Vote','Premier choix collectif : explorer à l\'est ou au nord ? La Conscience délibère.'],
                 'Implémenter le système de vote','Chaque décision = délibération visible. Certains membres dissidents.','high',
                 'Mécanique signature','Un membre peut \"se dissocier\" et agir seul — avec des conséquences sur tout le groupe.'],

                ['Les Oubliés',            'serie',     'draft',       'private',
                 'Drame : des personnes atteintes d\'amnésie totale sont regroupées dans une communauté et doivent se reconstruire sans passé.',
                 [['Drame','#F97316'],['Identité','#D946EF']],
                 [['La Communauté','Ancienne ferme réaménagée. Thérapeutique mais pas hôpital.','exterior',null],
                  ['Salle des Noms','Là où chacun choisit son nouveau nom.','interior',null]],
                 [['#7 — "Lou"','Lou','—','protagonist','A choisi le nom Lou. Ne sait pas pourquoi.','Arrivée il y a trois semaines. Trop à l\'aise dans les situations de crise.','Se souvenir ou décider que ça n\'a plus d\'importance.','Les deux à la fois, en tension.'],
                  ['Dr Anand','Priya','Anand','secondary','Neurologue qui dirige la communauté. Cache ses propres doutes.','A fondé la communauté après un patient qui s\'est reconstruit mieux qu\'avant.','Prouver que l\'identité peut être choisie.','Un espoir fragile.']],
                 ['saison','Saison 1 — Les Noms','Les oubliés apprennent à être.',
                  'episode','Épisode 1 — Jour Zéro','INT. SALLE DES NOMS — MATIN\nLou regarde le tableau. \"Quel nom veux-tu ?\" Elle répond sans réfléchir. \"Lou.\"'],
                 'Développer les 12 résidents','Chacun avec un fragment de passé qui filtre malgré l\'amnésie.','high',
                 'Question centrale','L\'identité est-elle dans les souvenirs ou dans les choix quotidiens ?'],

                ['Impact',                 'film',      'completed',   'public',
                 'Science-fiction dure : les 72 heures qui suivent la confirmation qu\'un astéroïde frappera la Terre dans six mois.',
                 [['Sci-Fi','#0EA5E9'],['Humanité','#10B981']],
                 [['Salle Blanche ONU','Là où la décision est prise.','interior',null],
                  ['Partout et Nulle Part','Le monde qui apprend la nouvelle.','exterior',null]],
                 [['Dr Yael Stern','Yael','Stern','protagonist','Astrophysicienne. A fait les calculs. Les refait. Mêmes résultats.','A passé sa vie à regarder le ciel. Il lui envoie maintenant quelque chose.','Que l\'humanité choisisse en connaissance de cause.','La vérité, même intolérable.'],
                  ['Secrétaire Général','Paz','Dominguez','secondary','Doit décider s\'il annonce. Comment. Quand.','Politicien de toute une vie. Jamais face à quelque chose de réel.','Que l\'humanité survive à l\'annonce.','La peur que la vérité soit pire que l\'ignorance.']],
                 ['act','Acte I — La Confirmation','Yael présente ses données à l\'ONU.',
                  'scene','Scène 1 — La Salle Blanche','INT. ONU — NUIT\nYael projette les chiffres. Silence de vingt secondes. Puis tout le monde parle en même temps.'],
                 'Écrire les réactions des 5 pays clés','Chacun réagit selon sa culture et ses intérêts.','high',
                 'Anti-spectacle','Pas de plan de sauvetage miraculeux. Le film traite du temps qu\'il reste, pas de la solution.'],
            ],
        ];

        $statuses    = ['in_progress', 'draft', 'completed'];
        $visibilities = [Project::VISIBILITY_PUBLIC, Project::VISIBILITY_PRIVATE, Project::VISIBILITY_UNPUBLISHED];

        foreach ($catalog as $userIndex => $projects) {
            $owner = $users[$userIndex];
            foreach ($projects as $p) {
                [$title, $type, $status, $vis, $desc, $tags, $locs, $chars, $struct, $taskTitle, $taskDesc, $taskPrio, $noteTitle, $noteContent] = $p;

                $visibility = match ($vis) {
                    'public'      => Project::VISIBILITY_PUBLIC,
                    'private'     => Project::VISIBILITY_PRIVATE,
                    default       => Project::VISIBILITY_UNPUBLISHED,
                };
                $modStatus = $visibility === Project::VISIBILITY_PUBLIC ? 'approved' : 'clear';

                $project = $this->makeProject($manager,
                    owner: $owner,
                    title: $title,
                    description: $desc,
                    type: $type,
                    status: $status,
                    visibility: $visibility,
                    moderationStatus: $modStatus,
                );
                $manager->flush();
                $this->configGenerator->generateConfigsForDepth($project, $type, 3);
                $manager->flush();

                foreach ($tags as [$tagName, $tagColor]) {
                    $this->makeTag($manager, $project, $tagName, $tagColor);
                }

                $locationObjects = [];
                foreach ($locs as $i => [$locName, $locDesc, $locType, $parentIdx]) {
                    $parent = $parentIdx !== null ? ($locationObjects[$parentIdx] ?? null) : null;
                    $locationObjects[$i] = $this->makeLocation($manager, $project, $locName, $locDesc, $locType, $parent);
                }

                foreach ($chars as [$cName, $cFirst, $cLast, $cRole, $cDesc, $cBio, $cGoals, $cMotiv]) {
                    $this->makeCharacter($manager, $project,
                        name: $cName, firstName: $cFirst, lastName: $cLast,
                        role: $cRole, description: $cDesc,
                        biography: $cBio, goals: $cGoals, motivations: $cMotiv,
                    );
                }

                [$parentType, $parentTitle, $parentDesc, $childType, $childTitle, $childContent] = $struct;
                $parentEl = $this->makeElement($manager, $project, null, $parentType, 1, $parentTitle, $parentDesc, 1);
                $this->makeElement($manager, $project, $parentEl, $childType, 2, $childTitle, $childContent, 1);

                $this->makeTask($manager, $project, $owner, $owner,
                    title: $taskTitle,
                    description: $taskDesc,
                    status: 'todo',
                    priority: $taskPrio,
                );

                $this->makeNote($manager, $project, $owner,
                    title: $noteTitle,
                    content: $noteContent,
                    status: 'note',
                    priority: 'normal',
                );

                $manager->flush();
            }
        }
    }

    private function makeUser(
        ObjectManager $manager,
        string $email, string $username,
        string $firstName, string $lastName,
        string $password,
        array  $roles    = ['ROLE_USER'],
        string $locale   = 'fr',
        bool   $isBanned = false,
    ): User {
        $user = new User();
        $user->setEmail($email)
             ->setUsername($username)
             ->setFirstName($firstName)
             ->setLastName($lastName)
             ->setRoles($roles)
             ->setPassword($this->hasher->hashPassword($user, $password))
             ->setAvatarColor(User::generateAvatarColor($username))
             ->setIsBanned($isBanned);
        $user->locale = $locale;
        $manager->persist($user);
        return $user;
    }

    private function makeProject(
        ObjectManager $manager,
        User   $owner,
        string $title,
        string $description,
        string $type,
        string $status            = 'draft',
        string $visibility        = Project::VISIBILITY_UNPUBLISHED,
        string $moderationStatus  = 'clear',
        int    $reportCount       = 0,
    ): Project {
        $project = new Project();
        $project->title            = $title;
        $project->description      = $description;
        $project->projectType      = $type;
        $project->status           = $status;
        $project->visibility       = $visibility;
        $project->moderationStatus = $moderationStatus;
        $project->reportCount      = $reportCount;
        $project->setCreatedBy($owner);
        $manager->persist($project);
        return $project;
    }

    private function makeTag(ObjectManager $m, Project $p, string $name, string $color): Tag
    {
        $tag = new Tag();
        $tag->setProject($p)->setName($name)->setColor($color);
        $m->persist($tag);
        return $tag;
    }

    private function makeLocation(
        ObjectManager $m, Project $project,
        string $name, string $description, string $type, ?Location $parent,
    ): Location {
        $loc = new Location();
        $loc->setProject($project)->setParent($parent);
        $loc->name        = $name;
        $loc->description = $description;
        $loc->type        = $type;
        $m->persist($loc);
        return $loc;
    }

    private function makeCharacter(
        ObjectManager $m, Project $project,
        string $name, string $firstName, string $lastName,
        string $role, string $description,
        string $biography = '', string $goals = '', string $motivations = '',
    ): Character {
        $c = new Character();
        $c->setProject($project)->setName($name)->setFirstName($firstName)
          ->setLastName($lastName)->setRole($role)->setDescription($description)
          ->setBiography($biography)->setGoals($goals)->setMotivations($motivations);
        $m->persist($c);
        return $c;
    }

    private function makeRelation(
        ObjectManager $m, Character $a, Character $b,
        string $type, string $description, bool $bidirectional = true,
    ): void {
        $rel = new CharacterRelation();
        $rel->setCharacterA($a)->setCharacterB($b);
        $rel->relationType    = $type;
        $rel->description     = $description;
        $rel->isBidirectional = $bidirectional;
        $m->persist($rel);
    }

    /** @param Tag[] $tags */
    private function makeElement(
        ObjectManager $m, Project $project, ?ScenarioElement $parent,
        string $elementType, int $depth, string $title, string $summary,
        int $orderIndex, array $tags = [], array $content = [], bool $hasContent = false,
    ): ScenarioElement {
        $el = new ScenarioElement();
        $el->setProject($project)->setParent($parent)->setElementType($elementType)
           ->setDepth($depth)->setTitle($title)->setSummary($summary)->setOrderIndex($orderIndex)
           ->setContent($content)->setHasContent($hasContent || !empty($content));
        foreach ($tags as $tag) {
            $el->addTag($tag);
        }
        $m->persist($el);
        return $el;
    }

    private function makeWorldEvent(
        ObjectManager $m, Project $project,
        string $title, int $year, string $description,
        ?int $month = null, ?int $day = null, ?Location $location = null,
    ): void {
        $ev = new WorldEvent();
        $ev->setProject($project)->setLocation($location);
        $ev->title       = $title;
        $ev->description = $description;
        $ev->year        = $year;
        $ev->month       = $month;
        $ev->day         = $day;
        $m->persist($ev);
    }

    private function makeTask(
        ObjectManager $m, Project $project,
        User $createdBy, ?User $assignedTo,
        string $title, string $description,
        string $status = 'todo', string $priority = 'normal',
        ?\DateTimeImmutable $dueDate = null,
    ): void {
        $task = new Task();
        $task->setProject($project)->setCreatedBy($createdBy)->setAssignedTo($assignedTo)
             ->setTitle($title)->setDescription($description)
             ->setStatus($status)->setPriority($priority)->setDueDate($dueDate);
        $m->persist($task);
    }

    private function makeNote(
        ObjectManager $m, Project $project, User $author,
        string $title, string $content,
        string $status = 'note', string $priority = 'normal',
    ): void {
        $note = new Note();
        $note->setProject($project)->setAuthor($author)
             ->setTitle($title)->setContent($content)
             ->setStatus($status)->setPriority($priority);
        $m->persist($note);
    }

    private function makeMember(ObjectManager $m, Project $p, User $u, string $role): void
    {
        $member = new ProjectMember();
        $member->setProject($p)->setUser($u)->setRole($role);
        $m->persist($member);
    }

    private function makeReport(
        ObjectManager $m, Project $project, User $reporter,
        string $reason, string $status = 'pending',
    ): void {
        $report = new Report();
        $report->targetType = Report::TYPE_PROJECT;
        $report->setTargetProject($project)->setReporter($reporter);
        $report->reason = $reason;
        $report->status = $status;
        $m->persist($report);
    }

    private function makeNotification(
        ObjectManager $m, User $user, string $content, bool $isRead = false,
    ): void {
        $notif = new Notification();
        $notif->setUser($user);
        $notif->content = $content;
        $notif->isRead  = $isRead;
        $m->persist($notif);
    }

    private function makeContact(
        ObjectManager $m,
        string $firstname, string $lastname, string $email,
        string $subject, string $message, bool $isRead = false,
    ): void {
        $contact = new Contact();
        $contact->setFirstname($firstname)->setLastname($lastname)->setEmail($email)
                ->setSubject($subject)->setMessage($message)->setIsRead($isRead);
        $m->persist($contact);
    }
}
