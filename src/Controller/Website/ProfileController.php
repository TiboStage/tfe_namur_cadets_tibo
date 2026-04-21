<?php

namespace App\Controller\Website;

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
        private readonly TranslatorInterface $translator,
    ) {}

    public function show(
        Request $request,
        UserPasswordHasherInterface $hasher,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Changement de mot de passe si renseigné
            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
            }

            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('profile.flash.updated', [], 'profile')
            );

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('website/profile/show.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
