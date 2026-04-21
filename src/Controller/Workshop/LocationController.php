<?php

namespace App\Controller\Workshop;

use App\Entity\Location;
use App\Entity\Project;
use App\Form\LocationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class LocationController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
    ) {}

    public function index(
        #[MapEntity(id: 'project_id')] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'view');

        return $this->render('workshop/locations/index.html.twig', [
            'project'   => $project,
            'locations' => $project->getLocations(),
        ]);
    }

    public function new(
        Request $request,
        #[MapEntity(id: 'project_id')] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $location = new Location();
        $location->setProject($project);

        $form = $this->createForm(LocationFormType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($location);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('location.flash.created', [], 'validators')
            );

            return $this->redirectToRoute('app_location_show', [
                'project_id' => $project->getId(),
                'id'         => $location->getId(),
            ]);
        }

        return $this->render('workshop/locations/new.html.twig', [
            'project'  => $project,
            'form'     => $form,
            'location' => $location,
        ]);
    }

    public function show(
        #[MapEntity(id: 'project_id')] Project $project,
        #[MapEntity(id: 'id')] Location $location
    ): Response {
        $this->checkProjectAccess($project, 'view');

        return $this->render('workshop/locations/show.html.twig', [
            'project'  => $project,
            'location' => $location,
        ]);
    }

    public function edit(
        Request $request,
        #[MapEntity(id: 'project_id')] Project $project,
        #[MapEntity(id: 'id')] Location $location
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $form = $this->createForm(LocationFormType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('location.flash.updated', [], 'validators')
            );

            return $this->redirectToRoute('app_location_show', [
                'project_id' => $project->getId(),
                'id'         => $location->getId(),
            ]);
        }

        return $this->render('workshop/locations/edit.html.twig', [
            'project'  => $project,
            'form'     => $form,
            'location' => $location,
        ]);
    }

    public function delete(
        Request $request,
        #[MapEntity(id: 'project_id')] Project $project,
        #[MapEntity(id: 'id')] Location $location
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        if ($this->isCsrfTokenValid('delete_location_' . $location->getId(), $request->getPayload()->get('_token'))) {
            $this->em->remove($location);
            $this->em->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('location.flash.deleted', [], 'validators')
            );
        }

        return $this->redirectToRoute('app_location_index', [
            'project_id' => $project->getId(),
        ]);
    }
}
