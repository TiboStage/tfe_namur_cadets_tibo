<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Gère les permissions par rôle au sein d'un projet.
 *
 * Les permissions sont stockées dans Project.settings['role_permissions'],
 * ce qui permet au propriétaire de les personnaliser sans migration.
 *
 * Si aucune config n'existe pour un rôle, on utilise les DEFAULTS.
 */
final class ProjectPermissionService
{
    // ─── Définition des permissions ───────────────────────────────────────────

    /** Groupes affichés dans l'UI (groupe → [clé → label]) */
    public const GROUPS = [
        'manuscript' => [
            'manuscript.view'   => 'Lire le manuscrit',
            'manuscript.edit'   => 'Modifier les scènes',
            'manuscript.delete' => 'Supprimer des scènes',
        ],
        'characters' => [
            'characters.view'   => 'Voir les personnages',
            'characters.edit'   => 'Modifier les personnages',
        ],
        'locations' => [
            'locations.view'    => 'Voir les lieux',
            'locations.edit'    => 'Modifier les lieux',
        ],
        'notes' => [
            'notes.view'        => 'Voir les notes',
            'notes.edit'        => 'Créer / modifier des notes',
        ],
        'tasks' => [
            'tasks.view'        => 'Voir les tâches',
            'tasks.complete'    => 'Terminer des tâches',
            'tasks.edit'        => 'Créer / modifier des tâches',
        ],
        'project' => [
            'settings.view'     => 'Voir les paramètres',
            'members.view'      => 'Voir les membres',
        ],
    ];

    /** Icônes Lucide pour chaque groupe */
    public const GROUP_ICONS = [
        'manuscript' => 'lucide:book-open',
        'characters' => 'lucide:user',
        'locations'  => 'lucide:map-pin',
        'notes'      => 'lucide:sticky-note',
        'tasks'      => 'lucide:check-square',
        'project'    => 'lucide:settings',
    ];

    /** Labels affichables des groupes */
    public const GROUP_LABELS = [
        'manuscript' => 'Manuscrit',
        'characters' => 'Personnages',
        'locations'  => 'Lieux',
        'notes'      => 'Notes',
        'tasks'      => 'Tâches',
        'project'    => 'Projet',
    ];

    // ─── Permissions par défaut ────────────────────────────────────────────────

    public const DEFAULTS = [
        'contributor' => [
            'manuscript.view',
            'characters.view',
            'locations.view',
            'notes.view',
            'tasks.view',
        ],
        'editor' => [
            'manuscript.view', 'manuscript.edit',
            'characters.view', 'characters.edit',
            'locations.view',  'locations.edit',
            'notes.view',      'notes.edit',
            'tasks.view',      'tasks.complete', 'tasks.edit',
        ],
        'lead' => [
            'manuscript.view', 'manuscript.edit', 'manuscript.delete',
            'characters.view', 'characters.edit',
            'locations.view',  'locations.edit',
            'notes.view',      'notes.edit',
            'tasks.view',      'tasks.complete', 'tasks.edit',
            'settings.view',   'members.view',
        ],
    ];

    public const ROLES = ['contributor', 'editor', 'lead'];

    public const ROLE_LABELS = [
        'contributor' => 'Contributeur',
        'editor'      => 'Éditeur',
        'lead'        => 'Co-responsable',
    ];

    public const ROLE_COLORS = [
        'contributor' => 'muted',
        'editor'      => 'green',
        'lead'        => 'blue',
    ];

    // ─── Lecture ──────────────────────────────────────────────────────────────

    /**
     * Retourne les permissions effectives d'un rôle pour ce projet.
     * Priorité : settings du projet > defaults.
     *
     * @return string[]
     */
    public function getEffectivePermissions(Project $project, string $role): array
    {
        $settings = $project->settings;

        if (isset($settings['role_permissions'][$role]) && is_array($settings['role_permissions'][$role])) {
            return $settings['role_permissions'][$role];
        }

        return self::DEFAULTS[$role] ?? [];
    }

    /**
     * Vérifie si un utilisateur a une permission donnée sur un projet.
     * Le propriétaire et les admins ont toujours tout.
     */
    public function can(UserInterface $user, Project $project, string $permission): bool
    {
        // Admin bypass
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Propriétaire — accès total
        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        // Cherche le membre correspondant
        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId()) {
                return in_array($permission, $this->getEffectivePermissions($project, $member->role), true);
            }
        }

        return false;
    }

    /**
     * Retourne le rôle d'un utilisateur dans un projet, ou null.
     */
    public function getMemberRole(UserInterface $user, Project $project): ?string
    {
        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return 'owner';
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId()) {
                return $member->role;
            }
        }

        return null;
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    /**
     * Enregistre les permissions d'un rôle dans les settings du projet.
     *
     * @param string[] $permissions
     */
    public function setRolePermissions(Project $project, string $role, array $permissions): void
    {
        $all    = self::allPermissionKeys();
        $clean  = array_values(array_intersect($permissions, $all)); // sécurité

        $s = $project->settings;
        $s['role_permissions'][$role] = $clean;
        $project->settings = $s;
    }

    /**
     * Réinitialise un rôle aux permissions par défaut
     * (supprime l'override dans les settings).
     */
    public function resetRole(Project $project, string $role): void
    {
        $s = $project->settings;
        unset($s['role_permissions'][$role]);
        $project->settings = $s;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** @return string[] toutes les clés de permission */
    public static function allPermissionKeys(): array
    {
        return array_merge(...array_values(array_map('array_keys', self::GROUPS)));
    }

    /**
     * Vérifie si les permissions d'un rôle ont été personnalisées
     * (différentes des defaults).
     */
    public function isCustomized(Project $project, string $role): bool
    {
        $settings = $project->settings;

        return isset($settings['role_permissions'][$role]);
    }
}
