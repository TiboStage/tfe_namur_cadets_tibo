<?php

/**
 * SCÉNART — EmailVerificationController
 *
 * Confirmation de l'adresse email après l'inscription :
 * 1. pending() → page "Vérifiez vos emails" + compteur + bouton renvoyer
 * 2. resend()  → renvoie le mail (mêmes limites que le mot de passe oublié)
 * 3. verify()  → lien signé cliqué dans le mail → compte confirmé
 *
 * SÉCURITÉ :
 * - Le lien est signé par verify-email-bundle (id + email + expiration) : non falsifiable
 * - L'email à qui renvoyer vient uniquement de la session (posé à l'inscription, ou
 *   après une connexion avec le BON mot de passe sur un compte non confirmé)
 * - Tant que le compte n'est pas confirmé, la connexion est refusée (App\Security\UserChecker)
 */

namespace App\Controller\Website;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\VerificationEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationController extends AbstractController
{
    // Clés de session partagées avec RegistrationController et SecurityController
    public const string SESSION_EMAIL     = 'verify_email.email';
    public const string SESSION_REMAINING = 'verify_email.remaining';
    public const string SESSION_RETRY_AT  = 'verify_email.retry_at';

    /**
     * Page "Vérifiez vos emails" affichée après l'inscription.
     */
    public function pending(Request $request): Response
    {
        $session = $request->getSession();
        $email = $session->get(self::SESSION_EMAIL);

        if (null === $email) {
            return $this->redirectToRoute('app_login', ['_locale' => $request->getLocale()]);
        }

        return $this->render('website/auth/verify_email_pending.html.twig', [
            'email'        => $email,
            // null si aucun envoi n'a encore été compté (ex. arrivée depuis la page de connexion)
            'remaining'    => $session->get(self::SESSION_REMAINING),
            'max_requests' => VerificationEmailSender::MAX_PER_HOUR,
            'retry_in'     => max(0, (int) $session->get(self::SESSION_RETRY_AT, 0) - time()),
        ]);
    }

    /**
     * Renvoi du mail de confirmation.
     */
    public function resend(
        Request $request,
        UserRepository $userRepository,
        VerificationEmailSender $sender,
        TranslatorInterface $translator,
    ): Response {
        $session = $request->getSession();
        $email = $session->get(self::SESSION_EMAIL);

        if (null === $email || !$this->isCsrfTokenValid('verify_email_resend', $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_login', ['_locale' => $request->getLocale()]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User || $user->isVerified()) {
            $this->clearSession($request);
            $this->addFlash('success', $translator->trans('verify_email.already_verified', [], 'auth'));

            return $this->redirectToRoute('app_login', ['_locale' => $request->getLocale()]);
        }

        try {
            $session->set(self::SESSION_REMAINING, $sender->send($user));
            $session->set(self::SESSION_RETRY_AT, time() + VerificationEmailSender::COOLDOWN_SECONDS);
            $this->addFlash('success', $translator->trans('verify_email.resent', [], 'auth'));
        } catch (RateLimitExceededException $e) {
            $wait = max(1, $e->getRetryAfter()->getTimestamp() - time());
            $this->addFlash('error', 1 === $e->getLimit()
                ? $translator->trans('forgot_password.too_soon', ['%seconds%' => $wait], 'auth')
                : $translator->trans('forgot_password.limit_reached', [
                    '%max%'     => $e->getLimit(),
                    '%minutes%' => (int) ceil($wait / 60),
                ], 'auth'));
        }

        return $this->redirectToRoute('app_verify_email_pending', ['_locale' => $request->getLocale()]);
    }

    /**
     * Lien cliqué dans le mail : confirme le compte puis renvoie vers la connexion.
     */
    public function verify(
        Request $request,
        UserRepository $userRepository,
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
    ): Response {
        $loginUrl = ['_locale' => $request->getLocale()];
        $user = $userRepository->find($request->query->getInt('id'));

        if (!$user instanceof User) {
            $this->addFlash('error', $translator->trans('verify_email.invalid_link', [], 'auth'));

            return $this->redirectToRoute('app_login', $loginUrl);
        }

        // Lien recliqué après confirmation : rien à faire
        if ($user->isVerified()) {
            $this->addFlash('success', $translator->trans('verify_email.already_verified', [], 'auth'));

            return $this->redirectToRoute('app_login', $loginUrl);
        }

        try {
            $verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), $user->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            // Lien expiré ou modifié → message du bundle (traduit) ; l'utilisateur peut
            // se reconnecter pour obtenir un bouton "renvoyer".
            $this->addFlash('error', $translator->trans($e->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_login', $loginUrl);
        }

        $user->setIsVerified(true);
        $em->flush();

        $this->clearSession($request);
        // Pré-remplit l'email sur le formulaire de connexion
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $user->getEmail());
        $this->addFlash('success', $translator->trans('verify_email.success', [], 'auth'));

        return $this->redirectToRoute('app_login', $loginUrl);
    }

    private function clearSession(Request $request): void
    {
        $session = $request->getSession();
        $session->remove(self::SESSION_EMAIL);
        $session->remove(self::SESSION_REMAINING);
        $session->remove(self::SESSION_RETRY_AT);
    }
}
