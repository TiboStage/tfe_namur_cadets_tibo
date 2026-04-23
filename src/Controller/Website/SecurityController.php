<?php

/**
 * SCÉNART — SecurityController
 *
 * Gère l'authentification des utilisateurs (connexion et déconnexion).
 *
 * FONCTIONNALITÉS :
 * - Page de connexion/inscription fusionnée (auth.html.twig)
 * - Toast de bienvenue à la connexion réussie
 * - Toast d'au revoir à la déconnexion
 * - Redirection automatique si déjà connecté
 *
 * ROUTES :
 * - /login (app_login) : Affiche le formulaire de connexion
 * - /logout (app_logout) : Déconnecte l'utilisateur (géré par Symfony Security)
 *
 * NOTES TECHNIQUES :
 * - Le nom app_login est obligatoire (Symfony le cherche par défaut)
 * - La méthode logout() est interceptée par security.yaml, elle ne s'exécute jamais
 * - Les toasts sont ajoutés via EventSubscriber qui écoute les événements de sécurité
 *
 * @author Thibault (SCÉNART)
 * @date Avril 2026
 */

namespace App\Controller\Website;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Page de connexion / inscription
     *
     * Affiche un formulaire fusionné contenant :
     * - Connexion (Login) à gauche
     * - Inscription (Register) à droite
     *
     * Si l'utilisateur est déjà connecté, il est redirigé vers le dashboard.
     *
     * @param AuthenticationUtils $authenticationUtils Service pour récupérer les erreurs de login
     * @return Response La page d'authentification
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // 1. Redirection si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workshop_dashboard');
        }

        // 2. Gestion de la partie CONNEXION (Login)
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // 3. Gestion de la partie INSCRIPTION (Register)
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user);

        // 4. Rendu du template fusionné
        return $this->render('website/auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $registrationForm->createView(),
        ]);
    }

    /**
     * Déconnexion de l'utilisateur
     *
     * Cette méthode ne s'exécute JAMAIS car elle est interceptée par Symfony Security
     * (configuré dans config/packages/security.yaml).
     *
     * Le toast d'au revoir est ajouté via LoginSuccessListener / LogoutSuccessListener.
     *
     * @throws \LogicException Toujours levée (méthode jamais exécutée)
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
