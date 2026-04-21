<?php

namespace App\Controller\Website;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    // Note : On garde le nom app_login car c'est celui que Symfony cherche par défaut
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
        // On crée l'objet User et le formulaire pour l'envoyer à la vue
        $user = new User();
        $registrationForm = $this->createForm(RegistrationFormType::class, $user);

        // 4. On envoie tout au template fusionné
        return $this->render('website/auth/auth.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'registrationForm' => $registrationForm->createView(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
