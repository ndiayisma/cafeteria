<?php
namespace App\Controller;

use App\Service\ApiClient;
use App\Service\CartService;
use App\Service\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class PanierController extends AbstractController
{
    public function __construct(private ApiClient $api, private CartService $cart, private CurrentUser $currentUser) {}

    #[Route('/panier', name: 'app_panier', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->currentUser->get()) {
            $this->addFlash('error', 'Connecte-toi pour voir ton panier.');
            return $this->redirect($this->currentUser->loginUrl());
        }

        return $this->render('order/cart.html.twig', [
            'panier' => $this->cart->all(),
            'total' => $this->cart->total(),
        ]);
    }

    #[Route('/panier/ajout/{id}', name: 'app_panier_ajout', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsCsrfTokenValid('panier')]
    public function ajout(int $id, Request $request): Response
    {
        // Le bouton "+" du panier renvoie vers le panier, celui du menu vers le menu
        $retour = 'panier' === $request->request->get('_retour') ? 'app_panier' : 'app_menu';

        // Nom et prix relus depuis l'API : on ne fait pas confiance au formulaire
        try {
            $plats = $this->api->get('/v1/menus/'.MenuController::MENU_ID)['plats'] ?? [];
        } catch (ExceptionInterface $e) {
            $this->addFlash('error', 'Impossible de joindre l\'API : '.$e->getMessage());
            return $this->redirectToRoute($retour);
        }

        foreach ($plats as $plat) {
            if ((int) $plat['id'] === $id) {
                $this->cart->add($id, $plat['nom'], (float) $plat['prix']);
                $this->addFlash('success', $plat['nom'].' ajouté au panier.');
                return $this->redirectToRoute($retour);
            }
        }

        $this->addFlash('error', 'Ce plat n\'est pas au menu du jour.');
        return $this->redirectToRoute($retour);
    }

    #[Route('/panier/retirer/{id}', name: 'app_panier_retirer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsCsrfTokenValid('panier')]
    public function retirer(int $id): Response
    {
        $this->cart->decrement($id);
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsCsrfTokenValid('panier')]
    public function supprimer(int $id): Response
    {
        $this->cart->remove($id);
        $this->addFlash('success', 'Plat retiré du panier.');
        return $this->redirectToRoute('app_panier');
    }
}
