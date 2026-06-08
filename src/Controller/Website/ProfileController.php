<?php

namespace App\Controller\Website;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface    $translator,
    ) {}

    public function show(
        Request $request,
        UserPasswordHasherInterface $hasher,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $usernameLocked  = !$user->canChangeUsername();
        $originalUsername = $user->getUsername();

        $form = $this->createForm(ProfileFormType::class, $user, [
            'username_locked' => $usernameLocked,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Changement de pseudo ──────────────────────────────────
            if (!$usernameLocked && $user->getUsername() !== $originalUsername) {
                $user->setUsernameChangedAt(new \DateTimeImmutable());
            }

            // ── Changement de mot de passe ───────────────────────────
            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
            }

            // ── Couleur d'avatar ──────────────────────────────────────
            $palette = ['#3B82F6','#8B5CF6','#EC4899','#F97316','#10B981','#06B6D4','#EAB308','#6366F1'];
            $color   = $form->get('avatarColor')->getData();
            if ($color && in_array($color, $palette, true)) {
                $user->setAvatarColor($color);
            }

            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('profile.updated', [], 'flash_messages')
            );

            return $this->redirectToRoute('app_profile', ['_locale' => $request->getLocale()]);
        }

        return $this->render('website/profile/show.html.twig', [
            'form'              => $form,
            'user'              => $user,
            'username_locked'   => $usernameLocked,
            'days_until_change' => $user->getDaysUntilNextUsernameChange(),
            'projects_count'    => $user->getProjects()->count(),
        ]);
    }
}
