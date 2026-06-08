<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service centralisé pour l'enregistrement des activités utilisateur.
 *
 * Convention d'action : '{entité}.{verbe}'
 *   Entités  : project, manuscript, character, location, note, task, world_event
 *   Verbes   : create, edit, delete, publish, complete, archive
 *
 * Exemple : 'character.create', 'project.publish', 'task.complete'
 */
final class ActivityLogService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Enregistre une action dans le journal d'activité.
     *
     * @param string       $action      Code de l'action (ex: 'character.create')
     * @param User         $user        Utilisateur qui a effectué l'action
     * @param Project|null $project     Projet concerné (null = action hors-projet)
     * @param string|null  $description Texte libre décrivant l'action (ex: "Personnage 'Alice' créé")
     */
    public function log(
        string   $action,
        User     $user,
        ?Project $project = null,
        ?string  $description = null,
    ): void {
        $log = new ActivityLog();
        $log->action = $action;
        $log->setUser($user);
        $log->setProject($project);
        $log->setDescription($description);

        $this->em->persist($log);
        // Pas de flush ici — on laisse le flush du controller faire le tout en une transaction.
        // Si l'action ne fait pas de flush (ex: consultation), appeler flushLog() explicitement.
    }

    /**
     * Flush immédiat — à appeler si le controller ne fait pas de flush après log().
     */
    public function flushLog(): void
    {
        $this->em->flush();
    }
}
