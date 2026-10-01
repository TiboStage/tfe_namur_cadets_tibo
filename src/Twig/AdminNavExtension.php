<?php

namespace App\Twig;

use App\Repository\DocumentationRepository;
use App\Repository\GenreRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Compteurs affichés dans la sidebar du panneau admin.
 *
 * Fonction (et non variable globale) pour que les requêtes ne soient
 * exécutées que sur les pages admin :
 *   {% set nav_counts = admin_nav_counts() %}
 */
class AdminNavExtension extends AbstractExtension
{
    private ?array $counts = null;

    public function __construct(
        private readonly UserRepository          $userRepository,
        private readonly ProjectRepository       $projectRepository,
        private readonly DocumentationRepository $documentationRepository,
        private readonly GenreRepository         $genreRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_nav_counts', [$this, 'getCounts']),
        ];
    }

    public function getCounts(): array
    {
        return $this->counts ??= [
            'users'         => $this->userRepository->countAll(),
            'projects'      => $this->projectRepository->countAll(),
            'flagged'       => $this->projectRepository->countFlagged(),
            'documentation' => $this->documentationRepository->count([]),
            'genres'        => $this->genreRepository->count([]),
        ];
    }
}
