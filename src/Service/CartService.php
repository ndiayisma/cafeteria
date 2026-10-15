<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

// Panier en session, clé "panier" : [plat_id => ['nom' => ..., 'prix' => ..., 'qte' => ...]]
class CartService
{
    private const KEY = 'panier';

    public function __construct(private RequestStack $requestStack) {}

    public function all(): array
    {
        return $this->session()->get(self::KEY, []);
    }

    public function add(int $id, string $nom, float $prix): void
    {
        $panier = $this->all();
        $panier[$id] ??= ['nom' => $nom, 'prix' => $prix, 'qte' => 0];
        $panier[$id]['qte']++;
        $this->save($panier);
    }

    // Retire une unité ; la ligne disparaît à 0
    public function decrement(int $id): void
    {
        $panier = $this->all();
        if (!isset($panier[$id])) return;
        if (--$panier[$id]['qte'] <= 0) unset($panier[$id]);
        $this->save($panier);
    }

    public function remove(int $id): void
    {
        $panier = $this->all();
        unset($panier[$id]);
        $this->save($panier);
    }

    public function total(): float
    {
        return array_sum(array_map(fn (array $l) => $l['prix'] * $l['qte'], $this->all()));
    }

    public function isEmpty(): bool
    {
        return [] === $this->all();
    }

    public function clear(): void
    {
        $this->session()->remove(self::KEY);
    }

    private function save(array $panier): void
    {
        $this->session()->set(self::KEY, $panier);
    }

    private function session(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
