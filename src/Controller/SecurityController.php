<?php

namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, ApiClient $api): Response
    {
        $session = $request->getSession();
        if ($session->has('etudiant_id')) {
            return $this->redirectToRoute('app_commande');
        }

        $error = null;
        $email = (string) $request->request->get('email', '');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('authenticate', (string) $request->request->get('_csrf_token'))) {
                $error = 'Jeton CSRF invalide, réessayez.';
            } else {
                try {
                    $etudiant = $api->post('/v1/login', [
                        'email' => $email,
                        'password' => (string) $request->request->get('password', ''),
                    ]);
                } catch (ClientExceptionInterface) {
                    $etudiant = []; // 400/401/404 : identifiants refusés par l'API
                } catch (ExceptionInterface) {
                    $etudiant = null; // API injoignable ou erreur 5xx
                }

                if ($etudiant === null) {
                    $error = "L'API est indisponible, réessayez plus tard.";
                } elseif (empty($etudiant['id'])) {
                    $error = 'Email ou mot de passe incorrect.';
                } else {
                    $session->migrate(true); // nouvel id de session après connexion
                    $session->set('etudiant_id', $etudiant['id']);
                    $session->set('etudiant', [
                        'id' => $etudiant['id'],
                        'nom' => $etudiant['nom'] ?? '',
                        'prenom' => $etudiant['prenom'] ?? '',
                        'email' => $etudiant['email'] ?? $email,
                        'is_staff' => (bool) ($etudiant['is_staff'] ?? false),
                    ]);

                    return $this->redirectToRoute('app_commande');
                }
            }
        }

        return $this->render('security/login.html.twig', [
            'last_email' => $email,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();

        return $this->redirectToRoute('app_login');
    }
}
