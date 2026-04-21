<?php

namespace App\Controller\Website;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class PagesController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('website/index.html.twig');
    }

    #[Route('/exemples', name: 'app_examples')]
    public function examples(): Response
    {
        return $this->render('website/pages/examples.html.twig');
    }

    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('website/pages/pricing.html.twig');
    }

    #[Route('/features', name: 'app_features')]
    public function features(): Response
    {
        return $this->render('website/pages/features.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            // On récupère TOUTES les données
            $emailSender = $request->request->get('email');
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname'); // Ajouté
            $subject = $request->request->get('subject');   // Ajouté
            $messageContent = $request->request->get('message');
            $privacy = $request->request->get('privacy');

            // 1. VALIDATION STRICTE (Back-end)
            // On vérifie que TOUT est présent et valide
            if (
                empty($emailSender) || !filter_var($emailSender, FILTER_VALIDATE_EMAIL) ||
                empty($firstname) ||
                empty($lastname) ||
                empty($subject) ||
                empty($messageContent) || strlen($messageContent) < 10 ||
                !$privacy
            ) {
                // Si une seule condition échoue, on affiche l'erreur globale
                $this->addFlash('danger', 'Veuillez remplir correctement tous les champs.');
                return $this->redirectToRoute('app_contact');
            }

            // 2. PRÉPARATION DE L'EMAIL
            $email = new Email()
                ->from('system@scenart.be') // Souvent mieux de mettre une adresse du domaine
                ->replyTo($emailSender)
                ->to('admin@scenart.be')
                ->subject('Contact [' . $subject . '] - ' . $firstname . ' ' . $lastname)
                ->text("De : $firstname $lastname ($emailSender)\nSujet : $subject\n\nMessage :\n$messageContent");

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Votre message a bien été envoyé !');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'envoi. Veuillez réessayer plus tard.');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('website/pages/contact.html.twig');
    }
}
