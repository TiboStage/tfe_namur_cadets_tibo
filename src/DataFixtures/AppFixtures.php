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

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── 1. UTILISATEURS ──────────────────────────────────────────────────

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

        // ── 2. PROJET FILM ───────────────────────────────────────────────────

        $filmProject = new Project();
        // Utilisation des Property Hooks PHP 8.4 (Assignation directe)
        $filmProject->title = 'Les Ombres de Valoria';
        $filmProject->description = 'Un thriller psychologique dans une ville corrompue.';
        $filmProject->projectType = 'film';
        $filmProject->status = 'in_progress';
        $filmProject->isPublic = true;
        $filmProject->setCreatedBy($thibault);

        $manager->persist($filmProject);

        // Ajout des configs spécifiques à ce projet (OBLIGATOIRE pour ta relation NOT NULL)
        $this->addConfigsToProject($manager, $filmProject);

        // Tags film
        $tagAction = $this->createTag($manager, $filmProject, 'Action', '#EF4444');
        $tagCle    = $this->createTag($manager, $filmProject, 'Clé du récit', '#10B981');

        // Structure narrative film
        $acte1 = $this->createScenarioElement($manager, $filmProject, null, 'act', 1, 'Acte I — L\'Éveil', 'Présentation.', 1);
        $seq1  = $this->createScenarioElement($manager, $filmProject, $acte1, 'sequence', 2, 'Séquence 1', 'Intro.', 1);

        $this->createScenarioElement(
            manager: $manager,
            project: $filmProject,
            parent: $seq1,
            elementType: 'scene', // Ce nom doit être identique à la variable dans la fonction
            depth: 3,
            title: 'Scène 1 — Le Pont',
            summary: 'Découverte du corps.',
            orderIndex: 1,
            tags: [$tagAction, $tagCle]
        );

        // Personnages film
        $sara = new Character();
        $sara->setProject($filmProject)
            ->setName('Sara Kane')
            ->setRole('protagonist')
            ->setDescription('Détective brillante.');
        $manager->persist($sara);

        // ── 3. PROJET JEU VIDÉO ─────────────────────────────────────────────

        $gameProject = new Project();
        $gameProject->title = 'Fragments d\'Éternité';
        $gameProject->description = 'RPG narratif post-apocalyptique.';
        $gameProject->projectType = 'jeu_video';
        $gameProject->status = 'draft';
        $gameProject->isPublic = false;
        $gameProject->setCreatedBy($thibault);

        $manager->persist($gameProject);

        // Ajout des configs spécifiques à ce projet
        $this->addConfigsToProject($manager, $gameProject);

        // Membre
        $member = new ProjectMember();
        $member->setProject($filmProject)->setUser($demo)->setRole('contributor');
        $manager->persist($member);

        $manager->flush();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createUser(ObjectManager $manager, string $email, string $username, string $firstName, string $lastName, string $password, array $roles = [], string $locale = 'fr'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setUsername($username)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles($roles)
            ->setPassword($this->hasher->hashPassword($user, $password));
        $user->locale = $locale;

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
        string $elementType, // <--- C'est ce nom que PHP cherche
        int $depth,
        string $title,
        string $summary,
        int $orderIndex,
        array $tags = []
    ): ScenarioElement {
        $element = new ScenarioElement();
        $element->setProject($project)
            ->setParent($parent)
            ->setElementType($elementType)
            ->setDepth($depth)
            ->setTitle($title)
            ->setSummary($summary)
            ->setOrderIndex($orderIndex);

        foreach ($tags as $tag) {
            $element->addTag($tag);
        }

        $manager->persist($element);
        return $element;
    }

    /**
     * Ajoute dynamiquement les configurations selon le type de projet
     * INDISPENSABLE car ta relation ProjectTypeConfig <-> Project est NOT NULL
     */
    private function addConfigsToProject(ObjectManager $manager, Project $project): void
    {
        $allConfigs = [
            'film' => [
                [1, 'act', 'Acte', 'Actes', false, false, '#3B82F6', 'layers'],
                [2, 'sequence', 'Séquence', 'Séquences', false, true, '#6366F1', 'list'],
                [3, 'scene', 'Scène', 'Scènes', true, true, '#8B5CF6', 'film'],
            ],
            'jeu_video' => [
                [1, 'chapter', 'Chapitre', 'Chapitres', false, false, '#10B981', 'book'],
                [2, 'level', 'Niveau', 'Niveaux', false, false, '#059669', 'grid'],
                [3, 'scene', 'Scène', 'Scènes', true, false, '#047857', 'film'],
            ]
        ];

        $type = $project->projectType;
        if (!isset($allConfigs[$type])) return;

        foreach ($allConfigs[$type] as [$depth, $elemType, $sing, $plur, $content, $dur, $color, $icon]) {
            $config = new ProjectTypeConfig();
            $config->setProject($project); // On lie la config au projet
            $config->setProjectType($type);
            $config->setDepth($depth);
            $config->setElementType($elemType);
            $config->setLabelSingular($sing);
            $config->setLabelPlural($plur);
            $config->setHasContent($content);
            $config->setHasDuration($dur);
            $config->setColor($color);
            $config->setIcon($icon);

            $manager->persist($config);
        }
    }
}
