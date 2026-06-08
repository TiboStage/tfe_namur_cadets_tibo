<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Documentation;
use App\Entity\DocumentationTranslation;
use App\Form\DocumentationFormType;
use App\Repository\DocumentationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class DocumentationAdminController extends AbstractController
{
    public function __construct(
        private readonly DocumentationRepository $docRepository,
        private readonly EntityManagerInterface  $em,
        private readonly TranslatorInterface     $translator,
    ) {}

    // ─── Liste ──────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $articles = $this->docRepository->findAllForAdmin();

        // Injecte la locale FR pour les titres dans la liste
        foreach ($articles as $article) {
            $article->setDisplayLocale('fr');
        }

        return $this->render('admin/documentation/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    // ─── Création ───────────────────────────────────────────────────────────

    public function new(Request $request): Response
    {
        $article = new Documentation();
        $form    = $this->createForm(DocumentationFormType::class, $article, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rawTranslations = $request->request->all('translations');

            // Génère le slug depuis le titre FR si non renseigné
            if (empty($article->slug)) {
                $frTitle     = trim($rawTranslations['fr']['title'] ?? '');
                $article->slug = Documentation::generateSlug($frTitle ?: 'article-' . uniqid());
            }

            // Crée les traductions fournies
            foreach (DocumentationTranslation::LOCALES as $locale) {
                $data    = $rawTranslations[$locale] ?? [];
                $title   = trim($data['title'] ?? '');
                $content = trim($data['content'] ?? '');

                if ($title !== '' || $content !== '') {
                    $translation = new DocumentationTranslation();
                    $translation->locale  = $locale;
                    $translation->title   = $title;
                    $translation->content = $content;
                    $translation->setDocumentation($article);
                    $this->em->persist($translation);
                }
            }

            $this->em->persist($article);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('admin.documentation_created', [], 'flash_messages'));

            return $this->redirectToRoute('admin_doc_index');
        }

        return $this->render('admin/documentation/form.html.twig', [
            'form'         => $form,
            'article'      => null,
            'translations' => $this->emptyTranslationsMap(),
        ]);
    }

    // ─── Édition ────────────────────────────────────────────────────────────

    public function edit(int $id, Request $request): Response
    {
        $article = $this->docRepository->findWithTranslations($id);

        if ($article === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(DocumentationFormType::class, $article, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rawTranslations = $request->request->all('translations');

            foreach (DocumentationTranslation::LOCALES as $locale) {
                $data    = $rawTranslations[$locale] ?? [];
                $title   = trim($data['title'] ?? '');
                $content = trim($data['content'] ?? '');

                $translation = $article->getTranslation($locale);

                if ($title !== '' || $content !== '') {
                    if ($translation === null) {
                        $translation = new DocumentationTranslation();
                        $translation->locale = $locale;
                        $translation->setDocumentation($article);
                        $this->em->persist($translation);
                    }
                    $translation->title   = $title;
                    $translation->content = $content;
                }
                // Ne supprime pas une traduction vide existante (préserve l'historique)
            }

            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('admin.documentation_updated', [], 'flash_messages'));

            return $this->redirectToRoute('admin_doc_index');
        }

        // Construit la map de traductions pour le template
        $translationsMap = $this->emptyTranslationsMap();
        foreach (DocumentationTranslation::LOCALES as $locale) {
            $t = $article->getTranslation($locale);
            if ($t !== null) {
                $translationsMap[$locale] = [
                    'title'   => $t->title,
                    'content' => $t->content,
                ];
            }
        }

        return $this->render('admin/documentation/form.html.twig', [
            'form'         => $form,
            'article'      => $article,
            'translations' => $translationsMap,
        ]);
    }

    // ─── Suppression ────────────────────────────────────────────────────────

    public function delete(int $id, Request $request): Response
    {
        $article = $this->docRepository->find($id);

        if ($article === null) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_doc_' . $id, $request->request->get('_token'))) {
            $this->em->remove($article);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('admin.documentation_deleted', [], 'flash_messages'));
        }

        return $this->redirectToRoute('admin_doc_index');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /** @return array<string, array{title: string, content: string}> */
    private function emptyTranslationsMap(): array
    {
        $map = [];
        foreach (DocumentationTranslation::LOCALES as $locale) {
            $map[$locale] = ['title' => '', 'content' => ''];
        }

        return $map;
    }
}
