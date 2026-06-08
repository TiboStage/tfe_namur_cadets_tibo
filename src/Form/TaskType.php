<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Task;
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
 * Formulaire pour créer/éditer une tâche de projet.
 * 
 * Les tâches permettent de gérer le travail en équipe avec des statuts,
 * priorités, et assignations.
 */
class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $project = $options['project'];

        $builder
            // Titre de la tâche
            ->add('title', TextType::class, [
                'label' => 'task.form.title',
                'translation_domain' => 'workshop_interface',
                'attr' => [
                    'placeholder' => 'task.form.title.placeholder',
                    'class' => 'form-control',
                    'maxlength' => 255,
                    'autofocus' => true,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'task.title.required',
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'task.title.too_short',
                        maxMessage: 'task.title.too_long',
                    ),
                ],
            ])

            // Description de la tâche
            ->add('description', TextareaType::class, [
                'label' => 'task.form.description',
                'translation_domain' => 'workshop_interface',
                'attr' => [
                    'placeholder' => 'task.form.description.placeholder',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'task.description.required',
                    ),
                ],
            ])

            // Statut de la tâche
            ->add('status', ChoiceType::class, [
                'label' => 'task.form.status',
                'translation_domain' => 'workshop_interface',
                'choices' => [
                    'task.status.todo' => 'todo',
                    'task.status.in_progress' => 'in_progress',
                    'task.status.review' => 'review',
                    'task.status.done' => 'done',
                    'task.status.archived' => 'archived',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            // Priorité de la tâche
            ->add('priority', ChoiceType::class, [
                'label' => 'task.form.priority',
                'translation_domain' => 'workshop_interface',
                'choices' => [
                    'task.priority.low' => 'low',
                    'task.priority.normal' => 'normal',
                    'task.priority.high' => 'high',
                    'task.priority.urgent' => 'urgent',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            // Assignation à un membre du projet
            ->add('assignedTo', EntityType::class, [
                'label' => 'task.form.assigned_to',
                'translation_domain' => 'workshop_interface',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getUsername();
                },
                'placeholder' => 'task.form.assigned_to.placeholder',
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
            'data_class' => Task::class,
            'translation_domain' => 'workshop_interface',
            'csrf_protection' => true,
            'csrf_token_id' => 'task_form',
        ]);

        // Option pour passer le projet
        $resolver->setRequired('project');
    }

    /**
     * Récupère tous les membres du projet (owner + members)
     */
    private function getProjectMembers($project): array
    {
        $members = [];

        $owner = $project->getOwner();
        if ($owner !== null) {
            $members[$owner->getId()] = $owner;
        }

        foreach ($project->getProjectMembers() as $projectMember) {
            $user = $projectMember->getUser();
            if ($user !== null) {
                $members[$user->getId()] = $user;
            }
        }

        return array_values($members);
    }
}
