<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder'  => 'profile.form.firstname',
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder'  => 'profile.form.lastname',
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('username', TextType::class, [
                'label'    => false,
                'disabled' => $options['username_locked'],
                'attr'     => [
                    'placeholder'  => 'profile.form.username',
                    'autocomplete' => 'off',
                    'pattern'      => '[a-zA-Z0-9_-]+',
                ],
                'constraints' => [
                    new NotBlank(message: 'registration.username.not_blank'),
                    new Length(min: 3, max: 30, minMessage: 'registration.username.too_short', maxMessage: 'registration.username.too_long'),
                    new Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: 'registration.username.invalid_chars'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => 'profile.form.email',
                    'autocomplete' => 'email'
                ],
            ])
            ->add('avatarColor', HiddenType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => ['data-profile-color-target' => 'colorInput'],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'required'        => false,
                'first_options'   => [
                    'label' => false,
                    'attr'  => [
                        'placeholder' => 'profile.form.new_password',
                        'autocomplete' => 'new-password'
                    ],
                ],
                'second_options'  => [
                    'label' => false,
                    'attr'  => [
                        'placeholder' => 'profile.form.confirm_password',
                        'autocomplete' => 'new-password'
                    ],
                ],
                'constraints' => [
                    new Length(min: 8, max: 4096, minMessage: 'registration.password.length'),
                    new Regex(pattern: '/[A-Z]/',    message: 'registration.password.uppercase'),
                    new Regex(pattern: '/[0-9]/',    message: 'registration.password.digit'),
                    new Regex(pattern: '/[\W_]/',    message: 'registration.password.special'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => User::class,
            'translation_domain' => 'validators',
            'username_locked'    => false,
        ]);
        $resolver->setAllowedTypes('username_locked', 'bool');
    }
}
