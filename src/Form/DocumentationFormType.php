<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Documentation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour les métadonnées d'un article de documentation.
 * Les traductions (titre + contenu par locale) sont gérées séparément
 * via des inputs HTML bruts dans le template.
 */
class DocumentationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('slug', TextType::class, [
                'label'    => 'Slug (URL)',
                'required' => false,
                'attr'     => [
                    'placeholder' => $isEdit
                        ? ''
                        : 'Laissez vide pour générer depuis le titre FR',
                    'readonly'    => $isEdit,
                ],
                'help' => $isEdit
                    ? 'Le slug ne peut pas être modifié après création.'
                    : null,
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'attr'  => [
                    'placeholder' => 'Ex: Démarrage, Personnages, Projets…',
                    'list'        => 'doc-categories',
                ],
            ])
            ->add('orderIndex', IntegerType::class, [
                'label' => 'Ordre dans la catégorie',
                'attr'  => ['min' => 0, 'max' => 999],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label'    => 'Publier cet article',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Documentation::class,
            'is_edit'    => false,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
