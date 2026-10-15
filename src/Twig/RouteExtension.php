<?php
namespace App\Twig;

use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// route_exists('app_login') : évite de casser le rendu tant qu'un coéquipier n'a pas créé sa route
class RouteExtension extends AbstractExtension
{
    public function __construct(private RouterInterface $router) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('route_exists', fn (string $name) => null !== $this->router->getRouteCollection()->get($name))];
    }
}
