<?php

namespace App\Controller\Workshop;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ActivityLogController extends AbstractController
{
    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    public function index(): Response
    {
        $user = $this->getUser();

        $activities = $this->activityLogRepository->findRecentByUser(
            $user->getId(),
            limit: 50
        );

        return $this->render('workshop/activity/index.html.twig', [
            'activities' => $activities,
        ]);
    }
}
