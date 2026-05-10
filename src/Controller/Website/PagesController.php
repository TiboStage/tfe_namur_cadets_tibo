<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur des pages du site web public
 *
 * Gère les pages statiques et le formulaire de contact
 */
final class PagesController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════
    // PAGES STATIQUES
    // ══════════════════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════════════════
    // FORMULAIRE DE CONTACT
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Formulaire de contact avec validation, envoi email et rate limiting
     *
     * Supporte deux modes :
     * - Requête classique : redirect avec flash message
     * - Requête AJAX : retourne JSON
     *
     * Rate limit : 5 messages par heure par IP
     */
    public function contact(
        Request $request,
        MailerInterface $mailer,
        EntityManagerInterface $em,
        #[Autowire(service: 'limiter.contact')]
        RateLimiterFactory $contactLimiter, // ← Injection automatique
    ): Response|JsonResponse {
        // ── Créer l'entité Contact et le formulaire ────────────────────
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        // ── Vérifier si le formulaire est soumis et valide ─────────────
        if ($form->isSubmitted() && $form->isValid()) {

            // ── Rate Limiting : Vérifier si l'utilisateur n'abuse pas ──
            $limiter = $contactLimiter->create($request->getClientIp());

            if (!$limiter->consume(1)->isAccepted()) {
                // Limite dépassée : refuser la requête
                $errorMessage = $this->translator->trans(
                    'contact.error.rate_limit',
                    [],
                    'validators'
                );

                // Si requête AJAX : retourner JSON
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => $errorMessage,
                    ], Response::HTTP_TOO_MANY_REQUESTS);
                }

                // Sinon : flash message et redirect
                $this->addFlash('danger', $errorMessage);
                return $this->redirectToRoute('app_contact', [
                    '_locale' => $request->getLocale()
                ]);
            }

            // ── Sauvegarder en base de données ─────────────────────────
            $em->persist($contact);
            $em->flush();

            // ── Préparer et envoyer l'email ─────────────────────────────
            try {
                $this->sendContactEmail($contact, $mailer);
                $successMessage = $this->translator->trans(
                    'contact.flash.success',
                    [],
                    'validators'
                );
            } catch (\Exception $e) {
                // En cas d'erreur d'envoi, on informe mais on ne bloque pas
                $successMessage = $this->translator->trans(
                    'contact.flash.mail_error',
                    [],
                    'validators'
                );
            }

            // ── Répondre selon le type de requête ──────────────────────

            // Si requête AJAX (modale ou validation live)
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => $successMessage,
                ]);
            }

            // Sinon requête classique : flash message et redirect
            $this->addFlash('success', $successMessage);
            return $this->redirectToRoute('app_contact', [
                '_locale' => $request->getLocale()
            ]);
        }

        // ── Afficher le formulaire ─────────────────────────────────────
        // Si requête AJAX pour charger le formulaire dans une modale
        if ($request->isXmlHttpRequest()) {
            return $this->render('website/pages/_contact_form.html.twig', [
                'form' => $form,
            ]);
        }

        // Sinon page complète
        return $this->render('website/pages/contact.html.twig', [
            'form' => $form,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Envoie l'email de contact à l'administrateur
     *
     * SÉCURITÉ : Tous les champs sont échappés avec htmlspecialchars()
     * pour éviter les injections XSS dans l'email
     *
     * @param Contact $contact Données du formulaire de contact
     * @param MailerInterface $mailer Service d'envoi d'emails
     * @throws \Exception Si l'envoi échoue
     */
    private function sendContactEmail(Contact $contact, MailerInterface $mailer): void
    {
        // ── Échapper TOUTES les données utilisateur (protection XSS) ──
        $firstname = htmlspecialchars($contact->getFirstname(), ENT_QUOTES, 'UTF-8');
        $lastname = htmlspecialchars($contact->getLastname(), ENT_QUOTES, 'UTF-8');
        $emailSender = htmlspecialchars($contact->getEmail(), ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($contact->getSubject(), ENT_QUOTES, 'UTF-8');

        // Échapper ET convertir les sauts de ligne en <br>
        $message = nl2br(htmlspecialchars($contact->getMessage(), ENT_QUOTES, 'UTF-8'));

        // ── Récupérer les adresses email depuis les variables d'environnement ──
        $fromEmail = $_ENV['MAILER_FROM'] ?? 'system@scenart.be';
        $adminEmail = $_ENV['MAILER_ADMIN'] ?? 'admin@scenart.be';

        // ── Construire l'email ─────────────────────────────────────────
        $email = (new Email())
            ->from($fromEmail)
            ->replyTo($contact->getEmail()) // Email brut pour le reply-to
            ->to($adminEmail)
            ->subject("Contact [{$subject}] - {$firstname}")
            ->html(sprintf(
                '<h2>Nouveau message de contact</h2>
                <p><strong>De :</strong> %s %s (%s)</p>
                <p><strong>Sujet :</strong> %s</p>
                <hr>
                <p><strong>Message :</strong></p>
                <div style="padding: 15px; background: #f5f5f5; border-left: 3px solid #FFC107;">
                    %s
                </div>',
                $firstname,
                $lastname,
                $emailSender,
                $subject,
                $message
            ));

        // ── Envoyer l'email ────────────────────────────────────────────
        $mailer->send($email);
    }
}
