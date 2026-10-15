<?php

namespace App\Controller\Admin;

use App\Form\PlatType;
use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[Route('/admin/plats')]
final class PlatController extends AbstractController
{
    public function __construct(private ApiClient $api) {}

    #[Route('', name: 'admin_plat_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if ($redirect = $this->checkStaff($request)) return $redirect;

        $plats = [];
        try {
            $plats = $this->api->get('/v1/plats');
        } catch (ExceptionInterface) {
            $this->addFlash('danger', 'Impossible de récupérer les plats.');
        }

        return $this->render('admin/plat/index.html.twig', [
            'plats' => $plats,
        ]);
    }

    #[Route('/nouveau', name: 'admin_plat_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($redirect = $this->checkStaff($request)) return $redirect;

        $form = $this->createForm(PlatType::class, ['disponible' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->api->post('/v1/plats', $form->getData());
                $this->addFlash('success', 'Plat créé.');
                return $this->redirectToRoute('admin_plat_index');
            } catch (ExceptionInterface) {
                $this->addFlash('danger', "L'API a refusé la création du plat.");
            }
        }

        return $this->render('admin/plat/form.html.twig', [
            'form' => $form,
            'titre' => 'Nouveau plat',
        ]);
    }

    #[Route('/{id<\d+>}/modifier', name: 'admin_plat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        if ($redirect = $this->checkStaff($request)) return $redirect;

        try {
            $plat = $this->api->get('/v1/plats/'.$id);
        } catch (ExceptionInterface) {
            $plat = [];
        }
        if (!$plat) {
            throw $this->createNotFoundException('Plat introuvable.');
        }

        $form = $this->createForm(PlatType::class, array_intersect_key($plat, array_flip(['nom', 'description', 'prix', 'disponible'])));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->api->put('/v1/plats/'.$id, $form->getData());
                $this->addFlash('success', 'Plat modifié.');
                return $this->redirectToRoute('admin_plat_index');
            } catch (ExceptionInterface) {
                $this->addFlash('danger', "L'API a refusé la modification du plat.");
            }
        }

        return $this->render('admin/plat/form.html.twig', [
            'form' => $form,
            'titre' => 'Modifier « '.($plat['nom'] ?? '#'.$id).' »',
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'admin_plat_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        if ($redirect = $this->checkStaff($request)) return $redirect;

        if ($this->isCsrfTokenValid('delete_plat_'.$id, (string) $request->request->get('_token'))) {
            try {
                $this->api->delete('/v1/plats/'.$id);
                $this->addFlash('success', 'Plat supprimé.');
            } catch (ExceptionInterface) {
                $this->addFlash('danger', "L'API a refusé la suppression du plat.");
            }
        }

        return $this->redirectToRoute('admin_plat_index');
    }

    // Back-office réservé aux comptes renvoyés par l'API avec is_staff = true
    private function checkStaff(Request $request): ?Response
    {
        $etudiant = $request->getSession()->get('etudiant');
        if (!$etudiant) {
            return $this->redirectToRoute('app_login');
        }
        if (empty($etudiant['is_staff'])) {
            throw new AccessDeniedHttpException('Accès réservé au personnel de la cafétéria.');
        }

        return null;
    }
}
