<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            // C'est ce qui te permet d'écrire {{ profile_btn() }} dans Twig
            new TwigFunction('profile_btn', [$this, 'renderProfileBtn'], [
                'is_safe' => ['html'],
                'needs_environment' => true
            ]),
        ];
    }

    public function renderProfileBtn(Environment $twig): string
    {
        // Assure-toi que le fichier existe bien dans templates/_components/
        return $twig->render('_components/_user_profile_dropdown.html.twig');
    }
}
