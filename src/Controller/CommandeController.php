<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final class CommandeController extends AbstractController
{
    #[Route('/commande', name: 'app_commande')]
    public function index(Request $request, ApiClient $api): Response
    {
        $etudiantId = $request->getSession()->get('etudiant_id');
        if (!$etudiantId) {
            $this->addFlash('warning', 'Connectez-vous pour voir vos commandes.');
            return $this->redirectToRoute('app_login');
        }

        $commandes = [];
        try {
            $commandes = $api->get('/v1/etudiants/'.$etudiantId.'/commandes');
        } catch (ExceptionInterface) {
            $this->addFlash('danger', 'Impossible de récupérer vos commandes pour le moment.');
        }

        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }
}
