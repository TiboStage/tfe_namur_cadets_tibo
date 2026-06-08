<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ProjectFeature;
use App\Repository\GenreRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Assigne aléatoirement 1 à 3 genres à chaque projet qui n'en a pas encore.
 *
 * Usage :
 *   php bin/console app:add-genres-to-projects
 *   php bin/console app:add-genres-to-projects --overwrite   # remplace les genres existants
 *   php bin/console app:add-genres-to-projects --dry-run     # simulation sans écriture
 */
#[AsCommand(
    name: 'app:add-genres-to-projects',
    description: 'Assigne 1 à 3 genres aléatoires aux projets qui n\'en ont pas.',
)]
class AddGenresToProjectsCommand extends Command
{
    public function __construct(
        private readonly ProjectRepository       $projectRepository,
        private readonly GenreRepository         $genreRepository,
        private readonly EntityManagerInterface  $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('overwrite', null, InputOption::VALUE_NONE,
                'Remplace les genres déjà existants (par défaut : skip les projets avec genres)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE,
                'Simulation — affiche ce qui serait fait sans écrire en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $overwrite = $input->getOption('overwrite');
        $dryRun    = $input->getOption('dry-run');

        $io->title('Attribution de genres aux projets');

        if ($dryRun) {
            $io->note('Mode dry-run — aucune écriture en base.');
        }

        // ── Charger tous les projets ─────────────────────────────────────────
        $projects = $this->projectRepository->findAll();
        $io->writeln(sprintf('Projets trouvés : <info>%d</info>', count($projects)));

        $assigned = 0;
        $skipped  = 0;
        $errors   = 0;

        $io->progressStart(count($projects));

        foreach ($projects as $project) {

            $io->progressAdvance();

            // Récupérer les genres déjà associés à ce projet
            $existingGenres = array_filter(
                $project->getProjectFeatures()->toArray(),
                fn($f) => $f->featureKey === 'genre'
            );

            // Skip si le projet a déjà des genres ET pas d'option --overwrite
            if (!empty($existingGenres) && !$overwrite) {
                $skipped++;
                continue;
            }

            // Charger les genres compatibles avec ce type de projet
            $compatibleGenres = $this->genreRepository->findActiveForType($project->projectType);

            if (empty($compatibleGenres)) {
                $io->warning(sprintf(
                    'Aucun genre actif pour le type "%s" (projet : %s)',
                    $project->projectType,
                    $project->title
                ));
                $errors++;
                continue;
            }

            // Supprimer les genres existants si --overwrite
            if (!empty($existingGenres) && $overwrite && !$dryRun) {
                foreach ($existingGenres as $feat) {
                    $this->em->remove($feat);
                }
            }

            // Choisir 1 à 3 genres aléatoires parmi les compatibles
            $count   = min(random_int(1, 3), count($compatibleGenres));
            $keys    = array_rand($compatibleGenres, $count);
            $picked  = is_array($keys)
                ? array_map(fn($k) => $compatibleGenres[$k], $keys)
                : [$compatibleGenres[$keys]];

            $slugs = array_map(fn($g) => $g->slug, $picked);

            if ($dryRun) {
                $io->writeln(sprintf(
                    "\n  <comment>%s</comment> (%s) → <info>%s</info>",
                    $project->title,
                    $project->projectType,
                    implode(', ', $slugs)
                ));
            } else {
                foreach ($picked as $genre) {
                    $feature = new ProjectFeature();
                    $feature->setProject($project);
                    $feature->featureKey = 'genre';
                    $feature->value      = $genre->slug;
                    $this->em->persist($feature);
                }
            }

            $assigned++;
        }

        $io->progressFinish();

        // ── Flush + résumé ───────────────────────────────────────────────────
        if (!$dryRun && $assigned > 0) {
            $this->em->flush();
        }

        $io->success(sprintf(
            '%d projet(s) traité(s) · %d ignoré(s) (genres déjà présents) · %d erreur(s)',
            $assigned,
            $skipped,
            $errors
        ));

        if ($dryRun) {
            $io->note('Dry-run terminé — aucune modification effectuée.');
        }

        return Command::SUCCESS;
    }
}
