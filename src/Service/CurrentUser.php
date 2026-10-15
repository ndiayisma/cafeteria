<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

// Étudiant connecté, en session sous "user" : ['id', 'email', 'role' => 'etudiant'|'admin'] (rempli par le login)
class CurrentUser
{
    public function __construct(
        private RequestStack $requestStack,
        private RouterInterface $router,
        #[Autowire('%env(bool:API_MOCK)%')] private bool $mock,
    ) {}

    public function get(): ?array
    {
        $session = $this->requestStack->getSession();
        $user = $session->get('user');

        // Repli tant que la route app_login n'existe pas : user factice en mode mock
        if (!$user && $this->mock && !$this->loginRouteExists()) {
            $user = ['id' => 1, 'email' => 'etudiant@campus.fr', 'role' => 'etudiant'];
            $session->set('user', $user);
        }

        return $user;
    }

    // Où envoyer un visiteur non connecté
    public function loginUrl(): string
    {
        return $this->router->generate($this->loginRouteExists() ? 'app_login' : 'app_menu');
    }

    private function loginRouteExists(): bool
    {
        return null !== $this->router->getRouteCollection()->get('app_login');
    }
}
