<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Formulaire de saisie du nouveau mot de passe.
 *
 * Mêmes règles que RegistrationFormType::plainPassword, pour rester cohérent
 * avec les critères affichés à l'inscription.
 */
class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Double saisie, comme à l'inscription : le second champ doit être identique au premier.
        $builder->add('plainPassword', RepeatedType::class, [
            'type'            => PasswordType::class,
            'mapped'          => false,
            'invalid_message' => 'registration.password.mismatch',
            'first_options'   => ['label' => false, 'attr' => ['autocomplete' => 'new-password']],
            'second_options'  => ['label' => false, 'attr' => ['autocomplete' => 'new-password']],
            // Placeholders traduits dans le Twig : 'reset_password.password' / 'reset_password.password_confirm'
            'constraints' => [
                new NotBlank(message: 'registration.password.not_blank'),
                new Length(min: 8, minMessage: 'registration.password.length', max: 4096),
                new Regex(pattern: '/[A-Z]/', message: 'registration.password.uppercase'),
                new Regex(pattern: '/[0-9]/', message: 'registration.password.digit'),
                new Regex(pattern: '/[\W_]/', message: 'registration.password.special'),
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
