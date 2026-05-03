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
    public function index(): Response
    {
        return $this->render('website/index.html.twig');
    }

    public function examples(): Response
    {
        return $this->render('website/pages/examples.html.twig');
    }

    public function pricing(): Response
    {
        return $this->render('website/pages/pricing.html.twig');
    }

    public function features(): Response
    {
        return $this->render('website/pages/features.html.twig');
    }

    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $emailSender = $request->request->get('email');
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $subject = $request->request->get('subject');
            $messageContent = $request->request->get('message');
            $privacy = $request->request->get('privacy');

            if (
                empty($emailSender) || !filter_var($emailSender, FILTER_VALIDATE_EMAIL) ||
                empty($firstname) ||
                empty($lastname) ||
                empty($subject) ||
                empty($messageContent) || strlen($messageContent) < 10 ||
                !$privacy
            ) {
                $this->addFlash('danger', 'Veuillez remplir correctement tous les champs.');
                return $this->redirectToRoute('app_contact');
            }

            $email = new Email()
                ->from('system@scenart.be')
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
