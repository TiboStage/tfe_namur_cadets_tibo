<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Alert
{
    public string $type = 'success';
    public string $message;

    public function getIcon(): string
    {
        return match ($this->type) {
            'danger'  => 'lucide:circle-x',
            'warning' => 'lucide:triangle-alert',
            'info'    => 'lucide:info',
            default   => 'lucide:check-circle',
        };
    }
}
