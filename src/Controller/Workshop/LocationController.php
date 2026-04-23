<?php

namespace App\Controller\Workshop;

use App\Entity\Location;
use App\Form\LocationFormType;

class LocationController extends AbstractProjectResourceController
{
    protected function getEntityClass(): string
    {
        return Location::class;
    }

    protected function getFormClass(): string
    {
        return LocationFormType::class;
    }

    protected function getRoutePrefix(): string
    {
        return 'app_location';
    }

    protected function getTemplatePrefix(): string
    {
        return 'workshop/projects/locations';
    }

    protected function getEntityVariable(): string
    {
        return 'location';
    }

    protected function getEntitiesVariable(): string
    {
        return 'locations';
    }

    protected function getTranslationKey(): string
    {
        return 'location';
    }
}
