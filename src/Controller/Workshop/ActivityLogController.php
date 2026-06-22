<?php

namespace App\Controller\Workshop;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ActivityLogController extends AbstractController
{
    // Catégories disponibles dans la sidebar (préfixe d'action → label + icône)
    private const CATEGORIES = [
        ''            => ['label' => 'Tout',          'icon' => 'lucide:activity'],
        'project'     => ['label' => 'Projets',       'icon' => 'lucide:folder'],
        'manuscript'  => ['label' => 'Manuscrit',     'icon' => 'lucide:file-text'],
        'character'   => ['label' => 'Personnages',   'icon' => 'lucide:users'],
        'location'    => ['label' => 'Lieux',         'icon' => 'lucide:map-pin'],
        'note'        => ['label' => 'Notes',         'icon' => 'lucide:notebook'],
        'task'        => ['label' => 'Tâches',        'icon' => 'lucide:square-check'],
        'world_event' => ['label' => 'Événements',    'icon' => 'lucide:calendar'],
    ];

    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    public function index(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $category = $request->query->getString('category', '');

        // Valider la catégorie
        if (!array_key_exists($category, self::CATEGORIES)) {
            $category = '';
        }

        $activities = $this->activityLogRepository->findActivityForUser(
            $user->getId(),
            $category !== '' ? $category : null,
            limit: 200
        );

        // ── Compteurs par catégorie (sur toutes les activités) ──────────────
        $allForCounts = $this->activityLogRepository->findActivityForUser($user->getId(), null, 200);
        $categoryCounts = [];
        foreach ($allForCounts as $log) {
            $prefix = explode('.', $log->getAction())[0] ?? '';
            $categoryCounts[$prefix] = ($categoryCounts[$prefix] ?? 0) + 1;
        }
        $totalCount = count($allForCounts);

        // ── Groupement par période ───────────────────────────────────────────
        $now        = new \DateTimeImmutable();
        $today      = $now->setTime(0, 0, 0);
        $yesterday  = $today->modify('-1 day');
        $weekStart  = $today->modify('monday this week');
        $monthStart = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);

        $grouped = [];
        foreach ($activities as $log) {
            $at = $log->getCreatedAt();
            if ($at >= $today) {
                $group = 'today';
            } elseif ($at >= $yesterday) {
                $group = 'yesterday';
            } elseif ($at >= $weekStart) {
                $group = 'this_week';
            } elseif ($at >= $monthStart) {
                $group = 'this_month';
            } else {
                $group = 'older';
            }
            $grouped[$group][] = $log;
        }

        return $this->render('workshop/activity/index.html.twig', [
            'grouped'         => $grouped,
            'activities'      => $activities,   // pour compatibilité éventuelle
            'category'        => $category,
            'categories'      => self::CATEGORIES,
            'category_counts' => $categoryCounts,
            'total_count'     => $totalCount,
        ]);
    }
}
