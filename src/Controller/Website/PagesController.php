<?php

namespace App\Controller\Website;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;


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
    public function contact(
        Request $request,
        MailerInterface $mailer,
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ): Response {
        $locale = $request->getLocale();

        if ($request->isMethod('POST')) {
            $emailSender = $request->request->get('email');
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $subject = $request->request->get('subject');
            $messageContent = $request->request->get('message');
            $privacy = $request->request->get('privacy');

            // 1. Sécurité CSRF
            if (!$this->isCsrfTokenValid('contact-form', $request->request->get('_token'))) {
                $this->addFlash('danger', $translator->trans('contact.error.csrf', [], 'validators'));
                return $this->redirectToRoute('app_contact', ['_locale' => $locale]);
            }

            // 2. Validations
            if (empty($emailSender)) {
                $this->addFlash('danger', $translator->trans('contact.email.not_blank', [], 'validators'));
                return $this->redirectToRoute('app_contact', ['_locale' => $locale]);
            }

            if (empty($messageContent) || strlen($messageContent) < 10) {
                $this->addFlash('danger', $translator->trans('contact.message.too_short', ['{{ limit }}' => 10], 'validators'));
                return $this->redirectToRoute('app_contact', ['_locale' => $locale]);
            }

            if (!$privacy) {
                $this->addFlash('danger', $translator->trans('contact.privacy.must_agree', [], 'validators'));
                return $this->redirectToRoute('app_contact', ['_locale' => $locale]);
            }

            // 3. Sauvegarde
            $contact = new Contact();
            $contact->setFirstname($firstname)
                ->setLastname($lastname)
                ->setEmail($emailSender)
                ->setSubject($subject)
                ->setMessage($messageContent);

            $em->persist($contact);
            $em->flush();

            // 4. Email
            $email = (new Email())
                ->from('system@scenart.be')
                ->replyTo($emailSender)
                ->to('admin@scenart.be')
                ->subject('Contact [' . $subject . '] - ' . $firstname)
                ->html("<p>Message de <b>$firstname $lastname</b> ($emailSender)</p><p>$messageContent</p>");

            try {
                $mailer->send($email);
                // Message de succès traduit
                $this->addFlash('success', $translator->trans('contact.flash.success', [], 'validators'));
            } catch (\Exception $e) {
                // Message d'avertissement traduit
                $this->addFlash('warning', $translator->trans('contact.flash.mail_error', [], 'validators'));
            }

            return $this->redirectToRoute('app_contact', ['_locale' => $locale]);
        }

        return $this->render('website/pages/contact.html.twig');
    }

}
