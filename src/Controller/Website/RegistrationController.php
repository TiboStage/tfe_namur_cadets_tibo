<?php

namespace App\Controller\Website;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use App\Service\TurnstileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator,
        TurnstileService $turnstile,
    ): Response {
        // Déjà connecté → dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workshop_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // ── Vérification Turnstile ──────────────────────────────────────────
        $captchaValid = true;
        if ($form->isSubmitted()) {
            $token = $request->request->get('cf-turnstile-response', '');
            $captchaValid = $turnstile->verify($token, $request->getClientIp());
            if (!$captchaValid) {
                $this->addFlash('error', $translator->trans('captcha.invalid', [], 'security'));
            }
        }

        if ($form->isSubmitted() && $form->isValid() && $captchaValid) {
            $user->setPassword(
                $hasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            // Couleur d'avatar : valeur soumise depuis le form ou générée depuis le username
            $submittedColor = $form->get('avatarColor')->getData();
            $palette = ['#3B82F6','#8B5CF6','#EC4899','#F97316','#10B981','#06B6D4','#EAB308','#6366F1'];
            if ($submittedColor && in_array($submittedColor, $palette, true)) {
                $user->setAvatarColor($submittedColor);
            } else {
                $user->setAvatarColor(\App\Entity\User::generateAvatarColor($user->username));
            }

            $em->persist($user);
            $em->flush();

            // Connexion automatique après inscription
            // authenticateUser() redirige automatiquement via LoginFormAuthenticator
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        // Formulaire invalide → retour à la page auth, panneau inscription ouvert
        return $this->render('website/auth/auth.html.twig', [
            'last_username'    => '',
            'error'            => null,
            'registrationForm' => $form,
        ], new Response(null, 422));
    }
}
