<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire de contact
 *
 * Gère la validation côté serveur du formulaire de contact.
 * La validation côté client est gérée par contact_form_controller.js
 */
class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ── Prénom ──────────────────────────────────────────────────
            ->add('firstname', TextType::class, [
                'label' => 'contact.form.labels.firstname',
                'translation_domain' => 'website',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'contact.firstname.not_blank',
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'contact.firstname.too_short',
                        maxMessage: 'contact.firstname.too_long',
                    ),
                ],
            ])

            // ── Nom ─────────────────────────────────────────────────────
            ->add('lastname', TextType::class, [
                'label' => 'contact.form.labels.lastname',
                'translation_domain' => 'website',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'contact.lastname.not_blank',
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'contact.lastname.too_short',
                        maxMessage: 'contact.lastname.too_long',
                    ),
                ],
            ])

            // ── Email ───────────────────────────────────────────────────
            ->add('email', EmailType::class, [
                'label' => 'contact.form.labels.email',
                'translation_domain' => 'website',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'contact.email.not_blank',
                    ),
                    new Assert\Email(
                        message: 'contact.email.invalid',
                    ),
                ],
            ])

            // ── Sujet (Select avec 3 options) ──────────────────────────
            ->add('subject', ChoiceType::class, [
                'label' => 'contact.form.labels.subject',
                'translation_domain' => 'website',
                'placeholder' => 'contact.form.placeholders.subject',
                'choices' => [
                    'contact.form.subjects.support' => 'support',
                    'contact.form.subjects.billing' => 'billing',
                    'contact.form.subjects.partnership' => 'partnership',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'contact.subject.not_blank',
                    ),
                ],
            ])

            // ── Message ─────────────────────────────────────────────────
            ->add('message', TextareaType::class, [
                'label' => 'contact.form.labels.message',
                'translation_domain' => 'website',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'maxlength' => 5000,
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'contact.message.not_blank',
                    ),
                    new Assert\Length(
                        min: 10,
                        max: 5000,
                        minMessage: 'contact.message.too_short',
                        maxMessage: 'contact.message.too_long',
                    ),
                ],
            ])

            // ── Acceptation politique de confidentialité ────────────────
            ->add('privacy', CheckboxType::class, [
                'label' => 'contact.form.privacy',
                'translation_domain' => 'website',
                'mapped' => false, // Non mappé car non stocké en DB
                'required' => true,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'contact.privacy.must_agree',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'translation_domain' => 'website',
            // Protection CSRF activée par défaut
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'contact_form',
        ]);
    }
}
