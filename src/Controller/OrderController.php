<?php
namespace App\Controller;

use App\Service\ApiClient;
use App\Service\CartService;
use App\Service\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class OrderController extends AbstractController
{
    // Paiement simulé : valeur envoyée à l'API => libellé affiché
    public const MOYENS_PAIEMENT = ['CB' => 'Carte bancaire', 'VISA' => 'Visa', 'MASTERCARD' => 'Mastercard'];

    public function __construct(private ApiClient $api, private CartService $cart, private CurrentUser $currentUser) {}

    #[Route('/commande/paiement', name: 'app_commande_paiement', methods: ['GET', 'POST'])]
    public function paiement(Request $request): Response
    {
        if (!$user = $this->currentUser->get()) {
            $this->addFlash('error', 'Connecte-toi pour commander.');
            return $this->redirect($this->currentUser->loginUrl());
        }
        if ($this->cart->isEmpty()) {
            $this->addFlash('error', 'Ton panier est vide.');
            return $this->redirectToRoute('app_menu');
        }

        if ($request->isMethod('POST')) {
            $moyen = $request->request->getString('moyen_paiement');

            if (!$this->isCsrfTokenValid('commande', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Formulaire expiré, merci de réessayer.');
            } elseif (!isset(self::MOYENS_PAIEMENT[$moyen])) {
                $this->addFlash('error', 'Choisis un moyen de paiement.');
            } else {
                try {
                    $commande = $this->api->createOrder((int) $user['id'], $this->cart->all(), $moyen);
                } catch (ExceptionInterface $e) {
                    $this->addFlash('error', 'La commande n\'a pas pu être enregistrée : '.$e->getMessage());
                    return $this->redirectToRoute('app_commande_paiement');
                }

                if (empty($commande['id'])) {
                    $this->addFlash('error', 'Réponse inattendue de l\'API : commande sans numéro.');
                    return $this->redirectToRoute('app_commande_paiement');
                }

                $this->cart->clear();
                return $this->redirectToRoute('app_commande_confirmation', ['id' => $commande['id']]);
            }

            return $this->redirectToRoute('app_commande_paiement');
        }

        return $this->render('order/checkout.html.twig', [
            'panier' => $this->cart->all(),
            'total' => $this->cart->total(),
            'moyens' => self::MOYENS_PAIEMENT,
        ]);
    }

    #[Route('/commande/{id}/confirmation', name: 'app_commande_confirmation', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirmation(int $id): Response
    {
        if (!$this->currentUser->get()) {
            return $this->redirect($this->currentUser->loginUrl());
        }

        return $this->render('order/confirmation.html.twig', ['id' => $id]);
    }

    #[Route('/commande/{id}', name: 'app_commande_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        if (!$user = $this->currentUser->get()) {
            $this->addFlash('error', 'Connecte-toi pour voir ta commande.');
            return $this->redirect($this->currentUser->loginUrl());
        }

        try {
            $commande = $this->api->get('/v1/orders/'.$id);
        } catch (ClientExceptionInterface $e) {
            // 4xx : commande inconnue ou accès refusé côté API
            $commande = [];
        } catch (ExceptionInterface $e) {
            $this->addFlash('error', 'Impossible de charger la commande : '.$e->getMessage());
            return $this->redirectToRoute('app_menu');
        }

        // Un étudiant ne voit que ses commandes (un admin les voit toutes)
        $autreEtudiant = isset($commande['etudiant_id']) && $commande['etudiant_id'] != $user['id'] && 'admin' !== $user['role'];
        if (!$commande || $autreEtudiant) {
            $this->addFlash('error', 'Commande n°'.$id.' introuvable.');
            return $this->redirectToRoute('app_menu');
        }

        return $this->render('order/show.html.twig', ['commande' => $commande, 'moyens' => self::MOYENS_PAIEMENT]);
    }
}
