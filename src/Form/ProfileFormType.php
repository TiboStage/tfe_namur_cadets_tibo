<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => 'profile.form.firstname',
                    'autocomplete' => 'given-name'
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => 'profile.form.lastname',
                    'autocomplete' => 'family-name'
                ],
            ])
            ->add('username', TextType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => 'profile.form.username',
                    'autocomplete' => 'username'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr'  => [
                    'placeholder' => 'profile.form.email',
                    'autocomplete' => 'email'
                ],
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
                    new Length(
                        min: 8,
                        max: 4096,
                        minMessage: 'registration.password.length',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => User::class,
            'translation_domain' => 'validators',
        ]);
    }
}
