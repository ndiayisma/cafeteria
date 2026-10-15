<?php
namespace App\Controller;

use App\Service\ApiClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class MenuController extends AbstractController
{
    // Menu du jour : l'API n'expose pas encore de "menu courant", on prend le menu 1
    public const MENU_ID = 1;

    public function __construct(private ApiClient $api) {}

    #[Route('/', name: 'app_menu', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $menu = $this->api->get('/v1/menus/'.self::MENU_ID);
        } catch (ExceptionInterface $e) {
            $this->addFlash('error', 'Impossible de charger le menu : '.$e->getMessage());
            $menu = null;
        }

        return $this->render('menu/index.html.twig', ['menu' => $menu]);
    }
}
