<?php

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'       => false,
                'empty_data' => '',
                'constraints' => [new NotBlank(message: 'location.name.not_blank')],
                'attr'        => ['placeholder' => 'location.form.name_placeholder'],
            ])
            ->add('type', ChoiceType::class, [
                'label'              => false,
                'empty_data' => '',
                'translation_domain' => 'validators',
                'choices'            => [
                    'location.type.interior'   => 'interior',
                    'location.type.exterior'   => 'exterior',
                    'location.type.fantasy'    => 'fantasy',
                    'location.type.historical' => 'historical',
                    'location.type.futuristic' => 'futuristic',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => [
                    'placeholder' => 'location.form.description_placeholder',
                    'rows'        => 6,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => Location::class,
            'translation_domain' => 'validators',
        ]);
    }
}
