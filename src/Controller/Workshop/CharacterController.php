<?php

namespace App\Controller\Workshop;

use App\Entity\Character;
use App\Entity\Project;
use App\Form\CharacterFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class CharacterController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
    ) {}

    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'view');

        return $this->render('workshop/characters/index.html.twig', [
            'project'    => $project,
            'characters' => $project->getCharacters(),
        ]);
    }

    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $character = new Character();
        $character->setProject($project);

        $form = $this->createForm(CharacterFormType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($character);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('character.flash.created', [], 'validators')
            );

            return $this->redirectToRoute('app_character_show', [
                'project_slug' => $project->getSlug(),
                'id'           => $character->getId(),
            ]);
        }

        return $this->render('workshop/characters/new.html.twig', [
            'project'   => $project,
            'form'      => $form,
            'character' => $character,
        ]);
    }

    public function show(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] Character $character
    ): Response {
        $this->checkProjectAccess($project, 'view');

        return $this->render('workshop/characters/show.html.twig', [
            'project'   => $project,
            'character' => $character,
        ]);
    }

    public function edit(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] Character $character
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $form = $this->createForm(CharacterFormType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('character.flash.updated', [], 'validators')
            );

            return $this->redirectToRoute('app_character_show', [
                'project_slug' => $project->getSlug(),
                'id'           => $character->getId(),
            ]);
        }

        return $this->render('workshop/characters/edit.html.twig', [
            'project'   => $project,
            'form'      => $form,
            'character' => $character,
        ]);
    }

    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] Character $character
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        if ($this->isCsrfTokenValid('delete_character_' . $character->getId(), $request->getPayload()->get('_token'))) {
            $this->em->remove($character);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('character.flash.deleted', [], 'validators')
            );
        }

        return $this->redirectToRoute('app_character_index', [
            'project_slug' => $project->getSlug(),
        ]);
    }
}
