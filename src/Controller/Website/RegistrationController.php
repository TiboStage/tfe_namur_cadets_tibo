<?php

namespace App\Controller\Website;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator,
    ): Response {
        // Déjà connecté → dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workshop_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $hasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

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
