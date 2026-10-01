<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire "mot de passe oublié" — un seul champ email.
 *
 * Réutilise les messages de validation de l'inscription (mêmes règles).
 */
class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => false,
            'attr'  => ['autocomplete' => 'email'],
            // Placeholder traduit dans le Twig : 'forgot_password.email'|trans
            'constraints' => [
                new NotBlank(message: 'registration.email.not_blank'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'validators',
        ]);
    }
}
