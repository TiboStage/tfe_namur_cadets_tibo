<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ErrorController extends AbstractController
{
    public function show(Throwable $exception, Request $request): Response
    {
        $statusCode = method_exists($exception, 'getStatusCode')
            ? $exception->getStatusCode()
            : 500;

        // Récupère la locale depuis la session (set par Symfony lors de la navigation)
        // Fallback sur la locale de la requête, puis 'fr' par défaut
        $locale = $request->getSession()->get('_locale')
            ?? $request->getLocale()
            ?? 'fr';

        dump($locale); // ← temporaire pour débugger

        // Vérifie que la locale est valide
        if (!in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        $template = "bundles/TwigBundle/Exception/error{$statusCode}.html.twig";

        if (!$this->container->get('twig')->getLoader()->exists($template)) {
            $template = 'bundles/TwigBundle/Exception/error.html.twig';
        }

        return $this->render($template, [
            'status_code' => $statusCode,
            'locale'      => $locale,
        ], new Response('', $statusCode));
    }
}
