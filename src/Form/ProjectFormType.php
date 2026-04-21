<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'empty_data' => '',
                'label' => false,
                'attr'  => ['placeholder' => 'project.form.title_placeholder'],

            ])
            ->add('description', TextareaType::class, [
                'empty_data' => '',  // ← si vide, envoie '' au lieu de null
                'label'    => false,
                'required' => false,
                'attr'     => [
                    'placeholder' => 'project.form.description_placeholder',
                    'rows'        => 4,
                ],
            ])
            ->add('projectType', ChoiceType::class, [
                'empty_data' => '',
                'label'   => false,
                'translation_domain' => 'validators',
                'choices' => [
                    'project.type.film'      => 'film',
                    'project.type.serie'     => 'serie',
                    'project.type.jeu_video' => 'jeu_video',
                    'project.type.custom'    => 'custom',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Project::class,
            'translation_domain' => 'validators',
        ]);
    }
}
