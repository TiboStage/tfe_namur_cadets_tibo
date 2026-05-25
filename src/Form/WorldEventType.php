<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use App\Entity\WorldEvent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire pour créer/éditer un événement de la chronologie narrative.
 * 
 * Les événements permettent de construire une timeline avec des dates narratives
 * (année, mois, jour) et peuvent être liés à des lieux du projet.
 */
class WorldEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $project = $options['project'];

        $builder
            // Titre de l'événement
            ->add('title', TextType::class, [
                'label' => 'world_event.form.title',
                'translation_domain' => 'workshop_interface',
                'attr' => [
                    'placeholder' => 'world_event.form.title.placeholder',
                    'class' => 'form-control',
                    'maxlength' => 255,
                    'autofocus' => true,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'world_event.title.required',
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'world_event.title.too_short',
                        maxMessage: 'world_event.title.too_long',
                    ),
                ],
            ])

            // Description de l'événement
            ->add('description', TextareaType::class, [
                'label' => 'world_event.form.description',
                'translation_domain' => 'workshop_interface',
                'required' => false,
                'attr' => [
                    'placeholder' => 'world_event.form.description.placeholder',
                    'class' => 'form-control',
                    'rows' => 5,
                ],
            ])

            // Année narrative (peut être négative pour avant "an 0")
            ->add('year', IntegerType::class, [
                'label' => 'world_event.form.year',
                'translation_domain' => 'workshop_interface',
                'attr' => [
                    'placeholder' => 'world_event.form.year.placeholder',
                    'class' => 'form-control',
                    'min' => -9999,
                    'max' => 9999,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'world_event.year.required',
                    ),
                    new Assert\Range(
                        min: -9999,
                        max: 9999,
                        notInRangeMessage: 'world_event.year.invalid_range',
                    ),
                ],
            ])

            // Mois (1-12, optionnel)
            ->add('month', IntegerType::class, [
                'label' => 'world_event.form.month',
                'translation_domain' => 'workshop_interface',
                'required' => false,
                'attr' => [
                    'placeholder' => 'world_event.form.month.placeholder',
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 12,
                ],
                'constraints' => [
                    new Assert\Range(
                        min: 1,
                        max: 12,
                        notInRangeMessage: 'world_event.month.invalid_range',
                    ),
                ],
            ])

            // Jour (1-31, optionnel)
            ->add('day', IntegerType::class, [
                'label' => 'world_event.form.day',
                'translation_domain' => 'workshop_interface',
                'required' => false,
                'attr' => [
                    'placeholder' => 'world_event.form.day.placeholder',
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 31,
                ],
                'constraints' => [
                    new Assert\Range(
                        min: 1,
                        max: 31,
                        notInRangeMessage: 'world_event.day.invalid_range',
                    ),
                ],
            ])

            // Lieu lié (optionnel)
            ->add('location', EntityType::class, [
                'label' => 'world_event.form.location',
                'translation_domain' => 'workshop_interface',
                'class' => Location::class,
                'choice_label' => 'name',
                'placeholder' => 'world_event.form.location.placeholder',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                // Filtrer uniquement les lieux du projet
                'choices' => $project ? $project->getLocations() : [],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorldEvent::class,
            'translation_domain' => 'workshop_interface',
            'csrf_protection' => true,
            'csrf_token_id' => 'world_event_form',
        ]);

        // Option pour passer le projet (nécessaire pour filtrer les lieux)
        $resolver->setRequired('project');
    }
}
