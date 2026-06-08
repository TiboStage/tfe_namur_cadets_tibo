<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Détecte et corrige les usernames invalides (espaces, caractères spéciaux)
 * qui ne correspondent pas au pattern de route [a-z0-9_-]+.
 *
 * Usage :
 *   php bin/console app:fix-usernames          ← aperçu (dry-run)
 *   php bin/console app:fix-usernames --apply  ← applique les corrections
 */
#[AsCommand(
    name: 'app:fix-usernames',
    description: 'Détecte et corrige les usernames invalides en base de données.',
)]
class FixUsernamesCommand extends Command
{
    public function __construct(
        private readonly UserRepository       $userRepository,
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

        $io->title('Vérification des usernames');

        if (!$apply) {
            $io->note('Mode aperçu (dry-run). Ajoutez --apply pour corriger.');
        }

        $users   = $this->userRepository->findAll();
        $invalid = [];

        foreach ($users as $user) {
            if (!preg_match('/^[a-z0-9_-]+$/', $user->username)) {
                $invalid[] = $user;
            }
        }

        if (empty($invalid)) {
            $io->success('Aucun username invalide trouvé. Tout est propre !');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($invalid as $user) {
            $fixed = preg_replace('/\s+/', '_', $user->username);   // espaces → _
            $fixed = preg_replace('/[^a-z0-9_-]/', '', $fixed);     // autres caractères invalides
            $fixed = trim($fixed, '_-');                              // nettoyer les bords
            $fixed = $fixed ?: 'user_' . $user->getId();             // fallback si vide

            $rows[] = [$user->getId(), $user->username, $fixed, $user->email];

            if ($apply) {
                // Écriture directe en contournant le property hook qui fait déjà strtolower
                $user->username = $fixed;
            }
        }

        $io->table(['ID', 'Username actuel', 'Username corrigé', 'Email'], $rows);

        if ($apply) {
            $this->em->flush();
            $io->success(sprintf('%d username(s) corrigé(s) avec succès.', count($invalid)));
        } else {
            $io->warning(sprintf(
                '%d username(s) invalide(s) trouvé(s). Lancez avec --apply pour corriger.',
                count($invalid)
            ));
        }

        return Command::SUCCESS;
    }
}
