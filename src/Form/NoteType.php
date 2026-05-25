<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Note;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire pour créer/éditer une note.
 * 
 * Les notes peuvent être de simples annotations ou des todos assignables.
 */
class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $project = $options['project'];

        $builder
            // Titre de la note
            ->add('title', TextType::class, [
                'label' => 'note.form.title',
                'translation_domain' => 'workshop_interface',
                'attr' => [
                    'placeholder' => 'note.form.title.placeholder',
                    'class' => 'form-control',
                    'maxlength' => 255,
                    'autofocus' => true,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'note.title.required',
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'note.title.too_short',
                        maxMessage: 'note.title.too_long',
                    ),
                ],
            ])

            // Contenu de la note
            ->add('content', TextareaType::class, [
                'label' => 'note.form.content',
                'translation_domain' => 'workshop_interface',
                'attr' => [
                    'placeholder' => 'note.form.content.placeholder',
                    'class' => 'form-control',
                    'rows' => 6,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'note.content.required',
                    ),
                ],
            ])

            // Statut de la note
            ->add('status', ChoiceType::class, [
                'label' => 'note.form.status',
                'translation_domain' => 'workshop_interface',
                'choices' => [
                    'note.status.note' => 'note',
                    'note.status.todo' => 'todo',
                    'note.status.done' => 'done',
                    'note.status.archived' => 'archived',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            // Priorité
            ->add('priority', ChoiceType::class, [
                'label' => 'note.form.priority',
                'translation_domain' => 'workshop_interface',
                'choices' => [
                    'note.priority.low' => 'low',
                    'note.priority.normal' => 'normal',
                    'note.priority.high' => 'high',
                    'note.priority.urgent' => 'urgent',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            // Assignation (optionnel)
            ->add('assignedTo', EntityType::class, [
                'label' => 'note.form.assigned_to',
                'translation_domain' => 'workshop_interface',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getUsername();
                },
                'placeholder' => 'note.form.assigned_to.placeholder',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                // Filtrer uniquement les membres du projet
                'choices' => $project ? $this->getProjectMembers($project) : [],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'translation_domain' => 'workshop_interface',
            'csrf_protection' => true,
            'csrf_token_id' => 'note_form',
        ]);

        // Option pour passer le projet
        $resolver->setRequired('project');
    }

    /**
     * Récupère tous les membres du projet
     */
    private function getProjectMembers($project): array
    {
        $members = [$project->getOwner()];
        
        foreach ($project->getProjectMembers() as $projectMember) {
            $members[] = $projectMember->getUser();
        }
        
        return array_unique($members);
    }
}
