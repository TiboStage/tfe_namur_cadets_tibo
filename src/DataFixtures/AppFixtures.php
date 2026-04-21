<?php

namespace App\DataFixtures;

use App\Entity\Character;
use App\Entity\CharacterRelation;
use App\Entity\Location;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\ProjectTypeConfig;
use App\Entity\ScenarioElement;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures de démonstration pour SCÉNART.
 * Contient uniquement les entités avec controllers implémentés.
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── 1. UTILISATEURS ──────────────────────────────────────────────────

        $admin = $this->createUser(
            manager:   $manager,
            email:     'admin@scenart.dev',
            username:  'admin',
            firstName: 'Super',
            lastName:  'Admin',
            password:  'Admin1234!',
            roles:     ['ROLE_SUPER_ADMIN'],
            locale:    'fr',
        );

        $thibault = $this->createUser(
            manager:   $manager,
            email:     'thibault@scenart.dev',
            username:  'thibault',
            firstName: 'Thibault',
            lastName:  'Dupont',
            password:  'Thibault1234!',
            roles:     ['ROLE_USER'],
            locale:    'fr',
        );

        $demo = $this->createUser(
            manager:   $manager,
            email:     'demo@scenart.dev',
            username:  'demo_user',
            firstName: 'Demo',
            lastName:  'User',
            password:  'Demo1234!',
            roles:     ['ROLE_USER'],
            locale:    'en',
        );

        // ── 2. PROJECT TYPE CONFIGS ──────────────────────────────────────────

        $this->createProjectTypeConfigs($manager);

        // ── 3. PROJET FILM ───────────────────────────────────────────────────

        $filmProject = new Project();
        $filmProject->setTitle('Les Ombres de Valoria')
            ->setDescription('Un thriller psychologique dans une ville corrompue où une détective tente de démasquer un tueur en série.')
            ->setProjectType('film')
            ->setStatus('in_progress')
            ->setIsPublic(true)
            ->setCreatedBy($thibault);
        $manager->persist($filmProject);

        // Tags film
        $tagAction    = $this->createTag($manager, $filmProject, 'Action',       '#EF4444');
        $tagTwist     = $this->createTag($manager, $filmProject, 'Twist',        '#F59E0B');
        $tagEmotion   = $this->createTag($manager, $filmProject, 'Émotion',      '#8B5CF6');
        $tagCle       = $this->createTag($manager, $filmProject, 'Clé du récit', '#10B981');
        $tagFlashback = $this->createTag($manager, $filmProject, 'Flashback',    '#6B7280');

        // Structure narrative film
        $acte1 = $this->createScenarioElement($manager, $filmProject, null, 'act', 1, 'Acte I — L\'Éveil', 'Présentation de Valoria et de Sara Kane.', 1);
        $seq1  = $this->createScenarioElement($manager, $filmProject, $acte1, 'sequence', 2, 'Séquence 1 — La ville qui dort', 'Introduction atmosphérique.', 1);

        $this->createScenarioElement(
            manager: $manager, project: $filmProject, parent: $seq1,
            elementType: 'scene', depth: 3,
            title: 'Scène 1 — Pont des Soupirs, 3h du matin',
            summary: 'Sara découvre le premier corps.',
            orderIndex: 1, tags: [$tagAction, $tagCle],
            content: [
                ['type' => 'slug',   'content' => 'EXT. PONT DES SOUPIRS - NUIT'],
                ['type' => 'action', 'content' => 'La brume enveloppe le vieux pont. SARA KANE (38 ans) se fraye un chemin entre les badauds.'],
                ['type' => 'char',   'content' => 'SARA'],
                ['type' => 'diag',   'content' => 'Encore un. Le troisième ce mois-ci.'],
            ],
        );

        $this->createScenarioElement($manager, $filmProject, $seq1, 'scene', 3, 'Scène 2 — Brigade criminelle', 'Sara présente les éléments communs.', 2, [$tagCle]);

        $acte2 = $this->createScenarioElement($manager, $filmProject, null, 'act', 1, 'Acte II — La Traque', 'Sara remonte la piste du tueur.', 2);
        $seq2  = $this->createScenarioElement($manager, $filmProject, $acte2, 'sequence', 2, 'Séquence 2 — Le philosophe fantôme', 'Identification du système de citations.', 1);

        $this->createScenarioElement($manager, $filmProject, $seq2, 'scene', 3, 'Scène 3 — Bibliothèque universitaire', 'Sara rencontre le Professeur Morel.', 1, [$tagTwist]);
        $this->createScenarioElement($manager, $filmProject, $seq2, 'scene', 3, 'Scène 4 — Flashback : enfance de Sara', 'Révélation sur le passé de Sara.', 2, [$tagFlashback, $tagEmotion]);
        $this->createScenarioElement($manager, $filmProject, null, 'act', 1, 'Acte III — La Vérité', 'Confrontation finale.', 3);

        // Personnages film
        $sara = new Character();
        $sara->setProject($filmProject)
            ->setName('Sara Kane')->setFirstName('Sara')->setLastName('Kane')
            ->setNickname('La Fantôme')->setRole('protagonist')
            ->setDescription('Détective brillante mais brisée par un passé traumatique.')
            ->setBiography('Sara a grandi dans les quartiers pauvres de Valoria.')
            ->setGoals('Arrêter le tueur philosophe.')
            ->setMotivations('La culpabilité. Le sens du devoir.')
            ->setCharacterArc('Sara passe de chasseuse à proie.')
            ->setAliases(['Sara', 'Sara Kane', 'la détective', 'Kane']);
        $manager->persist($sara);

        $morel = new Character();
        $morel->setProject($filmProject)
            ->setName('Professeur Morel')->setFirstName('Étienne')->setLastName('Morel')
            ->setRole('secondary')
            ->setDescription('Professeur de philosophie. Expert des stoïciens.')
            ->setBiography('Brillant académicien reconverti en consultant criminel.')
            ->setGoals('Aider Sara.')->setMotivations('Fascination intellectuelle.')
            ->setCharacterArc('Réalise que son enseignement a été détourné.')
            ->setAliases(['Morel', 'le Professeur', 'Étienne Morel']);
        $manager->persist($morel);

        $tueur = new Character();
        $tueur->setProject($filmProject)
            ->setName('L\'Ombre')->setFirstName('Victor')->setLastName('Lenz')
            ->setNickname('L\'Ombre')->setRole('antagonist')
            ->setDescription('Le tueur philosophe.')
            ->setBiography('Ancien étudiant traumatisé par le système judiciaire.')
            ->setGoals('Éveiller la ville à la corruption.')->setMotivations('Vengeance.')
            ->setCharacterArc('Du justicier idéaliste au monstre.')
            ->setAliases(['L\'Ombre', 'Victor', 'Victor Lenz']);
        $manager->persist($tueur);

        // Relations film
        $rel1 = new CharacterRelation();
        $rel1->setCharacterA($sara)->setCharacterB($morel)
            ->setRelationType('ally')->setDescription('Duo instinct + connaissance.')->setIsBidirectional(true);
        $manager->persist($rel1);

        $rel2 = new CharacterRelation();
        $rel2->setCharacterA($sara)->setCharacterB($tueur)
            ->setRelationType('rival')->setDescription('Le tueur est obsédé par Sara.')->setIsBidirectional(false);
        $manager->persist($rel2);

        $rel3 = new CharacterRelation();
        $rel3->setCharacterA($morel)->setCharacterB($tueur)
            ->setRelationType('mentor')->setDescription('Morel a été le mentor du tueur sans le savoir.')->setIsBidirectional(false);
        $manager->persist($rel3);

        // Lieux film
        $pont = new Location();
        $pont->setProject($filmProject)->setName('Pont des Soupirs')
            ->setDescription('Vieux pont de pierre. Lieu de tous les crimes.')->setType('exterior')
            ->setAliases(['le pont', 'Pont des Soupirs', 'le vieux pont']);
        $manager->persist($pont);

        $bibliotheque = new Location();
        $bibliotheque->setProject($filmProject)->setName('Bibliothèque universitaire')
            ->setDescription('Grande bibliothèque gothique. Royaume du Professeur Morel.')->setType('interior')
            ->setAliases(['la bibliothèque', 'la bibli']);
        $manager->persist($bibliotheque);

        $brigade = new Location();
        $brigade->setProject($filmProject)->setName('Brigade criminelle')
            ->setDescription('Bureau de Sara au commissariat.')->setType('interior')
            ->setAliases(['la brigade', 'le bureau', 'le commissariat']);
        $manager->persist($brigade);

        // Membre film
        $member = new ProjectMember();
        $member->setProject($filmProject)->setUser($demo)->setRole('contributor');
        $manager->persist($member);

        // ── 4. PROJET JEU VIDÉO ─────────────────────────────────────────────

        $gameProject = new Project();
        $gameProject->setTitle('Fragments d\'Éternité')
            ->setDescription('RPG narratif post-apocalyptique où les souvenirs sont une monnaie d\'échange.')
            ->setProjectType('jeu_video')
            ->setStatus('draft')
            ->setIsPublic(false)
            ->setCreatedBy($thibault);
        $manager->persist($gameProject);

        // Tags jeu
        $tagBoss  = $this->createTag($manager, $gameProject, 'Boss',        '#EF4444');
        $tagLore  = $this->createTag($manager, $gameProject, 'Lore',        '#8B5CF6');
        $tagChoix = $this->createTag($manager, $gameProject, 'Choix moral', '#F59E0B');

        // Structure jeu
        $chap1   = $this->createScenarioElement($manager, $gameProject, null, 'chapter', 1, 'Chapitre 1 — Le Collecteur', 'Introduction de Kael.', 1);
        $niveau1 = $this->createScenarioElement($manager, $gameProject, $chap1, 'level', 2, 'Niveau 1 — Les Décombres de Nova-City', 'Zone d\'introduction.', 1);

        $this->createScenarioElement($manager, $gameProject, $niveau1, 'scene', 3, 'Scène d\'ouverture — Réveil dans les ruines', 'Kael se réveille sans souvenirs.', 1, [$tagLore]);
        $this->createScenarioElement($manager, $gameProject, $niveau1, 'scene', 3, 'Première collecte — Mémoire du Garde', 'Tutoriel.', 2, [$tagChoix]);
        $this->createScenarioElement($manager, $gameProject, $niveau1, 'scene', 3, 'Boss — Le Gardien de la Mémoire Perdue', 'Premier affrontement.', 3, [$tagBoss, $tagLore]);
        $this->createScenarioElement($manager, $gameProject, null, 'chapter', 1, 'Chapitre 2 — Le Marché des Souvenirs', 'Économie souterraine des mémoires.', 2);

        // Personnages jeu
        $kael = new Character();
        $kael->setProject($gameProject)
            ->setName('Kael')->setFirstName('Kael')->setLastName('')
            ->setRole('protagonist')->setDescription('Collecteur de mémoires amnésique.')
            ->setBiography('L\'un des rares humains capables de toucher les mémoires résiduelles.')
            ->setGoals('Retrouver ses souvenirs.')->setMotivations('L\'identité. La survie.')
            ->setAliases(['Kael', 'le Collecteur']);
        $manager->persist($kael);

        $marchande = new Character();
        $marchande->setProject($gameProject)
            ->setName('La Marchande')->setFirstName('Sari')->setLastName('Voss')
            ->setNickname('La Marchande')->setRole('secondary')
            ->setDescription('Trafiquante de mémoires.')
            ->setBiography('Sari Voss a perdu sa famille dans la Catastrophe.')
            ->setGoals('Survivre.')->setMotivations('Amour maternel.')
            ->setAliases(['La Marchande', 'Sari', 'Sari Voss']);
        $manager->persist($marchande);

        // Lieux jeu
        $novaCity = new Location();
        $novaCity->setProject($gameProject)->setName('Nova-City')
            ->setDescription('Ruines de la grande métropole. Zone de départ.')->setType('exterior')
            ->setAliases(['Nova-City', 'les ruines', 'la ville']);
        $manager->persist($novaCity);

        // ── FLUSH ────────────────────────────────────────────────────────────

        $manager->flush();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createUser(
        ObjectManager $manager,
        string $email,
        string $username,
        string $firstName,
        string $lastName,
        string $password,
        array $roles = [],
        string $locale = 'fr',
    ): User {
        $user = new User();
        $user->setEmail($email)
            ->setUsername($username)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles($roles)
            ->setPassword($this->hasher->hashPassword($user, $password));

        $user->locale = $locale; // Property hook PHP 8.4

        $manager->persist($user);
        return $user;
    }

    private function createTag(ObjectManager $manager, Project $project, string $name, string $color): Tag
    {
        $tag = new Tag();
        $tag->setProject($project)->setName($name)->setColor($color);
        $manager->persist($tag);
        return $tag;
    }

    private function createScenarioElement(
        ObjectManager $manager,
        Project $project,
        ?ScenarioElement $parent,
        string $elementType,
        int $depth,
        string $title,
        string $summary,
        int $orderIndex,
        array $tags = [],
        array $content = [],
    ): ScenarioElement {
        $element = new ScenarioElement();
        $element->setProject($project)
            ->setParent($parent)
            ->setElementType($elementType)
            ->setDepth($depth)
            ->setTitle($title)
            ->setSummary($summary)
            ->setOrderIndex($orderIndex)
            ->setContent($content);

        foreach ($tags as $tag) {
            $element->addTag($tag);
        }

        $manager->persist($element);
        return $element;
    }

    private function createProjectTypeConfigs(ObjectManager $manager): void
    {
        $configs = [
            ['film',      1, 'act',      'Acte',     'Actes',     false, false, '#3B82F6', 'layers'],
            ['film',      2, 'sequence', 'Séquence', 'Séquences', false, true,  '#6366F1', 'list'],
            ['film',      3, 'scene',    'Scène',    'Scènes',    true,  true,  '#8B5CF6', 'film'],
            ['serie',     1, 'season',   'Saison',   'Saisons',   false, false, '#F59E0B', 'layers'],
            ['serie',     2, 'episode',  'Épisode',  'Épisodes',  false, true,  '#EF4444', 'tv'],
            ['serie',     3, 'act',      'Acte',     'Actes',     false, false, '#F97316', 'list'],
            ['serie',     4, 'scene',    'Scène',    'Scènes',    true,  true,  '#FB923C', 'film'],
            ['jeu_video', 1, 'chapter',  'Chapitre', 'Chapitres', false, false, '#10B981', 'book'],
            ['jeu_video', 2, 'level',    'Niveau',   'Niveaux',   false, false, '#059669', 'grid'],
            ['jeu_video', 3, 'scene',    'Scène',    'Scènes',    true,  false, '#047857', 'film'],
            ['custom',    1, 'part',     'Partie',   'Parties',   false, false, '#6B7280', 'folder'],
            ['custom',    2, 'chapter',  'Chapitre', 'Chapitres', false, false, '#4B5563', 'book'],
            ['custom',    3, 'scene',    'Scène',    'Scènes',    true,  false, '#374151', 'film'],
        ];

        foreach ($configs as [$type, $depth, $elementType, $singular, $plural, $hasContent, $hasDuration, $color, $icon]) {
            $config = new ProjectTypeConfig();
            $config->setProjectType($type)
                ->setDepth($depth)
                ->setElementType($elementType)
                ->setLabelSingular($singular)
                ->setLabelPlural($plural)
                ->setHasContent($hasContent)
                ->setHasDuration($hasDuration)
                ->setColor($color)
                ->setIcon($icon);
            $manager->persist($config);
        }
    }
}
