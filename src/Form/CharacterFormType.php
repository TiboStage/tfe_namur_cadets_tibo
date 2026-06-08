<?php

namespace App\Form;

use App\Entity\Character;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CharacterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'       => false,
                'empty_data'  => '',
                'constraints' => [new NotBlank(message: 'character.name.not_blank')],
            ])
            ->add('firstName', TextType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
            ])
            ->add('lastName', TextType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
            ])
            ->add('nickname', TextType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
            ])
            ->add('role', ChoiceType::class, [
                'label'   => false,
                'choices' => [
                    'character.role.protagonist' => 'protagonist',
                    'character.role.antagonist'  => 'antagonist',
                    'character.role.secondary'   => 'secondary',
                    'character.role.figurant'    => 'figurant',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['rows' => 3],
            ])
            ->add('biography', TextareaType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['rows' => 5],
            ])
            ->add('goals', TextareaType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['rows' => 3],
            ])
            ->add('motivations', TextareaType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['rows' => 3],
            ])
            ->add('characterArc', TextareaType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Character::class,
            'translation_domain' => 'workshop',
            'csrf_protection'    => true,
        ]);
    }
}
