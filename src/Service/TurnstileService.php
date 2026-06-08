<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Vérifie un token Cloudflare Turnstile côté serveur.
 *
 * API : POST https://challenges.cloudflare.com/turnstile/v0/siteverify
 * Doc : https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 *
 * Clés de test officielles (toujours valides en dev) :
 *   site key   → 1x00000000000000000000AA
 *   secret key → 1x0000000000000000000000000000000AA
 */
class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $secretKey,
    ) {}

    /**
     * Vérifie le token soumis par le formulaire.
     *
     * @param string      $token Token issu du champ "cf-turnstile-response"
     * @param string|null $ip    IP du client (optionnelle, renforce la vérification)
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        // Token vide → refus immédiat (pas d'appel HTTP inutile)
        if ($token === '') {
            return false;
        }

        try {
            $body = [
                'secret'   => $this->secretKey,
                'response' => $token,
            ];

            if ($ip !== null) {
                $body['remoteip'] = $ip;
            }

            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body'    => $body,
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            return $data['success'] ?? false;

        } catch (\Throwable) {
            // En cas d'erreur réseau → on laisse passer pour ne pas bloquer
            // les utilisateurs légitimes si l'API Cloudflare est temporairement down.
            return true;
        }
    }
}
