<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
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

    // ══════════════════════════════════════════════════════════════════════
    // PAGES LÉGALES
    // ══════════════════════════════════════════════════════════════════════

    public function legalCgu(): Response
    {
        return $this->render('website/pages/legal/cgu.html.twig');
    }

    public function legalPrivacy(): Response
    {
        return $this->render('website/pages/legal/privacy.html.twig');
    }

    public function legalCookies(): Response
    {
        return $this->render('website/pages/legal/cookies.html.twig');
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
        // Connecté → prénom, nom et email repris du compte (non modifiables),
        // le formulaire ne demande plus que le sujet et le message.
        $user = $this->getUser();
        $contact = new Contact();
        if ($user instanceof User) {
            $contact->setFirstname($user->getFirstName())
                ->setLastname($user->getLastName())
                ->setEmail($user->getEmail());
        }

        $form = $this->createForm(ContactType::class, $contact, [
            'with_identity' => !$user instanceof User,
        ]);
        $form->handleRequest($request);

        // ── Vérifier si le formulaire est soumis et valide ─────────────
        if ($form->isSubmitted() && $form->isValid()) {

            // ── Honeypot : si le champ piège est rempli, c'est un bot ──
            if ('' !== (string) $form->get('website')->getData()) {
                // Simuler un succès pour ne pas révéler le piège
                $this->addFlash('success', $this->translator->trans('contact.flash.success', [], 'validators'));
                return $this->redirectToRoute('app_contact', ['_locale' => $request->getLocale()]);
            }

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
                $this->sendContactEmail($contact, $mailer, $user instanceof User ? $user : null);
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
     * SÉCURITÉ : le contenu est rendu par Twig (emails/contact.html.twig),
     * qui échappe automatiquement toutes les données saisies (protection XSS).
     *
     * @param Contact $contact Données du formulaire de contact
     * @param MailerInterface $mailer Service d'envoi d'emails
     * @param User|null $user Compte de l'expéditeur s'il était connecté
     * @throws \Exception Si l'envoi échoue
     */
    private function sendContactEmail(Contact $contact, MailerInterface $mailer, ?User $user): void
    {
        // ── Récupérer les adresses email depuis les variables d'environnement ──
        $fromEmail = $_ENV['MAILER_FROM'] ?? 'system@scenart.be';
        $adminEmail = $_ENV['MAILER_ADMIN'] ?? 'admin@scenart.be';

        // Libellé lisible du sujet ("support" → "Support technique"), en français pour l'équipe
        $subjectLabel = $this->translator->trans('contact.form.subjects.' . $contact->getSubject(), [], 'website', 'fr');

        // ── Construire l'email ─────────────────────────────────────────
        // Le sujet du mail n'est pas passé par Twig : on retire les retours à la ligne
        // du prénom pour éviter toute injection d'en-tête.
        $firstname = preg_replace('/[\r\n]+/', ' ', (string) $contact->getFirstname());

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'Scénart'))
            ->replyTo(new Address($contact->getEmail(), trim($contact->getFirstname() . ' ' . $contact->getLastname())))
            ->to($adminEmail)
            ->subject("Contact [{$subjectLabel}] — {$firstname}")
            ->htmlTemplate('emails/contact.html.twig')
            ->context([
                'contact'      => $contact,
                'subjectLabel' => $subjectLabel,
                'sender'       => $user,
            ]);

        // ── Envoyer l'email ────────────────────────────────────────────
        $mailer->send($email);
    }
}
