<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Genre;
use App\Entity\GenreTranslation;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Administration des genres de projet.
 *
 * CRUD complet : liste, création, édition, suppression, toggle actif/inactif.
 * Chaque genre nécessite une traduction en FR, NL et EN (obligatoires).
 */
#[IsGranted('ROLE_ADMIN')]
class GenreAdminController extends AbstractController
{
    public function __construct(
        private readonly GenreRepository       $genreRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface   $translator,
    ) {}

    // ── Liste ────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        return $this->render('admin/genre/index.html.twig', [
            'genres' => $this->genreRepository->findAllForAdmin(),
        ]);
    }

    // ── Création ─────────────────────────────────────────────────────────────

    public function new(Request $request): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            ['errors' => $errors, 'genre' => $genre] = $this->handleForm($request, new Genre());

            if (empty($errors)) {
                $this->em->persist($genre);
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('admin.genre_created', [], 'flash_messages'));
                return $this->redirectToRoute('admin_genre_index');
            }
        }

        return $this->render('admin/genre/form.html.twig', [
            'genre'  => new Genre(),
            'errors' => $errors ?? [],
            'mode'   => 'new',
        ]);
    }

    // ── Édition ──────────────────────────────────────────────────────────────

    public function edit(Request $request, int $id): Response
    {
        $genre = $this->genreRepository->find($id);
        if (!$genre) {
            throw $this->createNotFoundException('Genre introuvable.');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            ['errors' => $errors, 'genre' => $genre] = $this->handleForm($request, $genre);

            if (empty($errors)) {
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('admin.genre_updated', [], 'flash_messages'));
                return $this->redirectToRoute('admin_genre_index');
            }
        }

        return $this->render('admin/genre/form.html.twig', [
            'genre'  => $genre,
            'errors' => $errors,
            'mode'   => 'edit',
        ]);
    }

    // ── Toggle actif / inactif ────────────────────────────────────────────────

    public function toggle(Request $request, int $id): Response
    {
        $genre = $this->genreRepository->find($id);
        if (!$genre) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('genre_toggle_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('csrf.token_invalid', [], 'flash_messages'));
            return $this->redirectToRoute('admin_genre_index');
        }

        $genre->isActive = !$genre->isActive;
        $this->em->flush();

        $this->addFlash('success', $genre->isActive
            ? 'Genre activé.'
            : 'Genre désactivé (toujours visible sur les projets existants).'
        );

        return $this->redirectToRoute('admin_genre_index');
    }

    // ── Suppression ───────────────────────────────────────────────────────────

    public function delete(Request $request, int $id): Response
    {
        $genre = $this->genreRepository->find($id);
        if (!$genre) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('genre_delete_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('csrf.token_invalid', [], 'flash_messages'));
            return $this->redirectToRoute('admin_genre_index');
        }

        $this->em->remove($genre);
        $this->em->flush();
        $this->addFlash('success', $this->translator->trans('admin.genre_deleted', [], 'flash_messages'));

        return $this->redirectToRoute('admin_genre_index');
    }

    // ── Helper formulaire ─────────────────────────────────────────────────────

    /**
     * Lit le POST, valide et hydrate le genre.
     * Retourne ['errors' => [...], 'genre' => Genre].
     */
    private function handleForm(Request $request, Genre $genre): array
    {
        $errors = [];

        // ── Slug ──────────────────────────────────────────────────────────────
        $slug = strtolower(trim($request->request->getString('slug')));
        $slug = preg_replace('/[^a-z0-9_]+/', '_', $slug);
        $slug = trim($slug, '_');

        if ($slug === '') {
            $errors[] = 'Le slug est obligatoire.';
        } elseif (strlen($slug) > 50) {
            $errors[] = 'Le slug ne peut pas dépasser 50 caractères.';
        } else {
            // Unicité du slug (sauf si c'est le genre courant)
            $existing = $this->genreRepository->findOneBy(['slug' => $slug]);
            if ($existing && $existing->getId() !== $genre->getId()) {
                $errors[] = "Le slug « {$slug} » est déjà utilisé par un autre genre.";
            } else {
                $genre->slug = $slug;
            }
        }

        // ── Types de projet ───────────────────────────────────────────────────
        $rawTypes = $request->request->all('projectTypes');
        $validTypes = ['film', 'serie', 'jeu_video'];
        $genre->projectTypes = array_values(array_intersect($rawTypes ?: [], $validTypes));

        // ── Ordre ─────────────────────────────────────────────────────────────
        $genre->orderIndex = max(0, (int) $request->request->get('orderIndex', 0));

        // ── Traductions (FR + NL + EN obligatoires) ───────────────────────────
        $rawTranslations = $request->request->all('translations');

        foreach (GenreTranslation::LOCALES as $locale) {
            $label = trim($rawTranslations[$locale]['label'] ?? '');

            if ($label === '') {
                $flag   = GenreTranslation::LOCALE_FLAGS[$locale];
                $name   = GenreTranslation::LOCALE_LABELS[$locale];
                $errors[] = "La traduction {$flag} {$name} est obligatoire.";
                continue;
            }

            // Trouver ou créer la traduction pour cette locale
            $translation = $genre->getTranslation($locale);
            if ($translation === null) {
                $translation = new GenreTranslation();
                $translation->setGenre($genre);
                $translation->locale = $locale;
                $genre->getTranslations()->add($translation);
                $this->em->persist($translation);
            }
            $translation->label = $label;
        }

        return ['errors' => $errors, 'genre' => $genre];
    }
}
