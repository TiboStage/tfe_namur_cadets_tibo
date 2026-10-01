<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Régénère les slugs des projets à partir de leur titre (accents mal translittérés
 * par l'ancien slugify). Le suffixe aléatoire de 8 caractères est conservé.
 *
 * Usage :
 *   php bin/console app:regenerate-project-slugs          ← aperçu (dry-run)
 *   php bin/console app:regenerate-project-slugs --apply  ← applique les corrections
 */
#[AsCommand(
    name: 'app:regenerate-project-slugs',
    description: 'Régénère les slugs des projets à partir de leur titre.',
)]
class RegenerateProjectSlugsCommand extends Command
{
    public function __construct(
        private readonly ProjectRepository      $projectRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'apply',
            null,
            InputOption::VALUE_NONE,
            'Applique réellement les corrections (sans cette option : dry-run seulement)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $apply = $input->getOption('apply');

        $io->title('Régénération des slugs de projets');

        if (!$apply) {
            $io->note('Mode aperçu (dry-run). Ajoutez --apply pour corriger.');
        }

        $rows = [];
        foreach ($this->projectRepository->findAll() as $project) {
            $old = $project->getSlug();
            $project->regenerateSlug($project->getTitle());

            if ($project->getSlug() !== $old) {
                $rows[] = [$project->getTitle(), $old, $project->getSlug()];
            }
        }

        if ($rows === []) {
            $io->success('Tous les slugs sont déjà corrects.');
            return Command::SUCCESS;
        }

        $io->table(['Titre', 'Ancien slug', 'Nouveau slug'], $rows);

        if ($apply) {
            $this->em->flush();
            $io->success(sprintf('%d slug(s) corrigé(s).', count($rows)));
        } else {
            $io->warning(sprintf('%d slug(s) à corriger. Relancez avec --apply.', count($rows)));
        }

        return Command::SUCCESS;
    }
}
