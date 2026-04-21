<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire d'inscription.
 *
 * Les contraintes utilisent le domaine 'validators' (validators.fr.yaml).
 * Les placeholders et labels sont gérés dans auth.html.twig via le domaine 'auth'.
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => false,
                // Placeholder traduit dans le Twig : 'register.firstname'|trans
            ])
            ->add('lastName', TextType::class, [
                'label' => false,
                // Placeholder traduit dans le Twig : 'register.lastname'|trans
            ])
            ->add('username', TextType::class, [
                'label' => false,
                // Placeholder traduit dans le Twig : 'register.username'|trans
                'constraints' => [
                    new NotBlank(
                        message: 'registration.username.not_blank'
                    ),
                    new Length(
                        min: 3,
                        max: 30,
                        minMessage: 'registration.username.too_short',
                        maxMessage: 'registration.username.too_long',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr'  => ['autocomplete' => 'email'],
                // Placeholder traduit dans le Twig : 'register.email'|trans
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label'  => false,
                'attr'   => ['autocomplete' => 'new-password'],
                // Placeholder traduit dans le Twig : 'register.password'|trans
                'constraints' => [
                    new NotBlank(
                        message: 'registration.password.not_blank'
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'registration.password.length',
                        max: 4096,
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped'      => false,
                'label'       => false,
                'constraints' => [
                    new IsTrue(
                        message: 'registration.terms.must_agree'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => User::class,
            // Domaine utilisé pour traduire les messages de contrainte
            'translation_domain' => 'validators',
        ]);
    }
}
