<?php

namespace App\Controller\Workshop;

use App\Entity\Character;
use App\Form\CharacterFormType;

class CharacterController extends AbstractProjectResourceController
{
    protected function getEntityClass(): string
    {
        return Character::class;
    }

    protected function getFormClass(): string
    {
        return CharacterFormType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'app_character';
    }

    protected function getTemplatePrefix(): string
    {
        return 'workshop/projects/characters';
    }

    protected function getEntityVariable(): string
    {
        return 'character';
    }

    protected function getEntitiesVariable(): string
    {
        return 'characters';
    }

    protected function getTranslationKey(): string
    {
        return 'character';
    }
}
