<?php

/**
 * SCÉNART — ResetPasswordController
 *
 * Gère le parcours "mot de passe oublié" :
 * 1. request()   → formulaire email, génère un token et envoie le lien par mail
 * 2. checkEmail() → page de confirmation générique
 * 3. reset()      → validation du token + saisie du nouveau mot de passe
 *
 * SÉCURITÉ :
 * - Le même message est affiché que le compte existe ou non (pas d'énumération d'emails)
 * - Le token n'est jamais stocké en clair (voir ResetPasswordRequest / bundle SymfonyCasts)
 * - Le token est retiré de l'URL et déplacé en session dès la première requête,
 *   pour ne pas rester dans l'historique du navigateur
 */

namespace App\Controller\Website;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Message\SendResetPasswordEmailMessage;
use App\Repository\UserRepository;
use App\Service\TurnstileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    /** Nombre maximum de demandes par email et par heure (doit suivre rate_limiter.yaml). */
    private const MAX_REQUESTS_PER_HOUR = 3;

    /** Délai entre deux demandes, en secondes (doit suivre reset_password.yaml / rate_limiter.yaml). */
    private const COOLDOWN_SECONDS = 120;

    // Clés de session utilisées pour le compteur affiché sur la page "vérifiez vos emails"
    private const SESSION_EMAIL     = 'reset_password.email';
    private const SESSION_REMAINING = 'reset_password.remaining';
    private const SESSION_RETRY_AT  = 'reset_password.retry_at';

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly EntityManagerInterface $em,
        #[Autowire(service: 'limiter.reset_password_cooldown')]
        private readonly RateLimiterFactoryInterface $cooldownLimiter,
        #[Autowire(service: 'limiter.reset_password')]
        private readonly RateLimiterFactoryInterface $hourlyLimiter,
        #[Autowire(service: 'limiter.reset_password_ip')]
        private readonly RateLimiterFactoryInterface $ipLimiter,
    ) {
    }

    /**
     * Étape 1 — formulaire "mot de passe oublié".
     */
    public function request(
        Request $request,
        MessageBusInterface $bus,
        TranslatorInterface $translator,
        TurnstileService $turnstile,
        UserRepository $userRepository,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_workshop_dashboard');
        }

        // Pré-remplit l'email quand on revient via "Renvoyer l'email"
        $session = $request->getSession();
        $form = $this->createForm(ResetPasswordRequestFormType::class, [
            'email' => $session->get(self::SESSION_EMAIL),
        ]);
        $form->handleRequest($request);

        // ── Vérification Turnstile (anti-abus : évite le spam d'emails) ──
        $captchaValid = true;
        if ($form->isSubmitted()) {
            $token = $request->request->get('cf-turnstile-response', '');
            $captchaValid = $turnstile->verify($token, $request->getClientIp());
            if (!$captchaValid) {
                $this->addFlash('error', $translator->trans('captcha.invalid', [], 'security'));
            }
        }

        if ($form->isSubmitted() && $form->isValid() && $captchaValid) {
            $email = trim((string) $form->get('email')->getData());
            // Clé de limitation insensible à la casse : "Tibo@x.be" et "tibo@x.be" partagent le même compteur
            $limiterKey = mb_strtolower($email);

            // ── Limites d'envoi ──────────────────────────────────────────
            // Comptées AVANT de chercher le compte, que l'email existe ou non :
            // le compteur affiché ne révèle donc jamais si un compte est inscrit.
            $ipLimit = $this->ipLimiter->create($request->getClientIp() ?? 'unknown')->consume();
            $cooldown = $ipLimit->isAccepted() ? $this->cooldownLimiter->create($limiterKey)->consume() : null;
            $hourly = $cooldown?->isAccepted() ? $this->hourlyLimiter->create($limiterKey)->consume() : null;

            if (!$ipLimit->isAccepted() || !$hourly?->isAccepted()) {
                $rejected = $hourly ?? $cooldown ?? $ipLimit;
                $wait = max(1, $rejected->getRetryAfter()->getTimestamp() - time());

                $this->addFlash('error', $rejected === $cooldown
                    ? $translator->trans('forgot_password.too_soon', ['%seconds%' => $wait], 'auth')
                    : $translator->trans('forgot_password.limit_reached', [
                        '%max%'     => $rejected->getLimit(),
                        '%minutes%' => (int) ceil($wait / 60),
                    ], 'auth'));

                // 429 et pas 200 : Turbo n'affiche pas une réponse 200 à un envoi de formulaire
                return $this->render('website/auth/forgot_password.html.twig', [
                    'requestForm' => $form,
                ], new Response(status: Response::HTTP_TOO_MANY_REQUESTS));
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            // Compte trouvé → on tente d'envoyer l'email.
            // Compte absent → on ne le révèle jamais :
            // dans tous les cas l'utilisateur atterrit sur la même page de confirmation.
            if ($user instanceof User) {
                try {
                    $this->dispatchResetPasswordEmail($user, $bus);
                } catch (ResetPasswordExceptionInterface) {
                    // Filet de sécurité du bundle (throttle_limit) → on ignore silencieusement
                }
            }

            $session->set(self::SESSION_EMAIL, $email);
            $session->set(self::SESSION_REMAINING, $hourly->getRemainingTokens());
            $session->set(self::SESSION_RETRY_AT, time() + self::COOLDOWN_SECONDS);

            return $this->redirectToRoute('app_check_email');
        }

        // Formulaire envoyé mais refusé (CAPTCHA invalide…) → 422, sinon Turbo n'affiche rien
        return $this->render('website/auth/forgot_password.html.twig', [
            'requestForm' => $form,
        ], new Response(status: $form->isSubmitted()
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK));
    }

    /**
     * Étape 2 — page de confirmation générique ("si un compte existe...").
     */
    public function checkEmail(Request $request): Response
    {
        $session = $request->getSession();

        return $this->render('website/auth/check_email.html.twig', [
            // null si on arrive sur la page sans être passé par le formulaire
            'remaining'    => $session->get(self::SESSION_REMAINING),
            'max_requests' => self::MAX_REQUESTS_PER_HOUR,
            'retry_in'     => max(0, (int) $session->get(self::SESSION_RETRY_AT, 0) - time()),
        ]);
    }

    /**
     * Étape 3 — validation du token + saisie du nouveau mot de passe.
     */
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        ?string $token = null,
    ): Response {
        if ($token) {
            // On retire le token de l'URL pour qu'il ne reste pas dans l'historique
            // du navigateur, puis on redirige vers la même page sans le token.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password', ['_locale' => $request->getLocale()]);
        }

        $token = $this->getTokenFromSession();

        // Pas de token en session : lien déjà utilisé (retour arrière après le reset),
        // session expirée ou accès direct à l'URL → on renvoie vers une nouvelle demande.
        if (null === $token) {
            $this->addFlash('error', $translator->trans('reset_password.invalid_token', [], 'auth'));

            return $this->redirectToRoute('app_forgot_password_request', ['_locale' => $request->getLocale()]);
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', $translator->trans('reset_password.invalid_token', [], 'auth'));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Token à usage unique : on le supprime avant même de finir le traitement.
            $this->resetPasswordHelper->removeResetRequest($token);

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $this->em->flush();

            $this->cleanSessionAfterReset();
            $request->getSession()->remove(self::SESSION_EMAIL);
            $request->getSession()->remove(self::SESSION_REMAINING);
            $request->getSession()->remove(self::SESSION_RETRY_AT);

            $this->addFlash('success', $translator->trans('auth.reset_password_success', [], 'flash_messages'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('website/auth/reset_password.html.twig', [
            'resetForm' => $form,
        ]);
    }

    /**
     * Génère le token puis dispatche l'envoi de l'email sur le bus Messenger —
     * le worker `messenger:consume async` fait l'appel SMTP réel en arrière-plan
     * (voir SendResetPasswordEmailMessageHandler), pour ne pas bloquer la réponse HTTP.
     */
    private function dispatchResetPasswordEmail(User $user, MessageBusInterface $bus): void
    {
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        $bus->dispatch(new SendResetPasswordEmailMessage(
            $user->getId(),
            $resetToken->getToken(),
            $resetToken->getExpiresAt(),
        ));
    }
}
