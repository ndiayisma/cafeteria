<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiClient
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(API_BASE_URL)%')] private string $baseUrl,
        #[Autowire('%env(bool:API_MOCK)%')] private bool $mock,
        #[Autowire('%kernel.project_dir%/mock')] private string $mockDir,
    ) {}

    public function get(string $path): array
    {
        if ($this->mock) return $this->readMock('get', $path);
        return $this->client->request('GET', $this->baseUrl.$path)->toArray();
    }

    public function post(string $path, array $data): array
    {
        if ($this->mock) return $this->readMock('post', $path);
        return $this->client->request('POST', $this->baseUrl.$path, ['json' => $data])->toArray();
    }

    /**
     * Crée une commande et renvoie la réponse de l'API ({ "id", ... }).
     * $panier : [plat_id => ['nom', 'prix', 'qte']] (format de CartService)
     *
     * TODO : la route POST /v1/orders n'existe pas encore côté Django.
     * Adapter ici seulement (URL, noms des champs, forme de la réponse) quand elle sera dispo.
     */
    public function createOrder(int $etudiantId, array $panier, string $moyenPaiement): array
    {
        $lignes = [];
        foreach ($panier as $platId => $ligne) {
            $lignes[] = ['plat_id' => $platId, 'quantite' => $ligne['qte']];
        }

        return $this->post('/v1/orders', [
            'etudiant_id' => $etudiantId,
            'lignes' => $lignes,
            'moyen_paiement' => $moyenPaiement,
        ]);
    }

    // "/v1/menus/1" → mock/get_v1_menus_1.json
    private function readMock(string $method, string $path): array
    {
        $file = $this->mockDir.'/'.$method.'_'.str_replace('/', '_', trim($path, '/')).'.json';
        return is_file($file) ? json_decode(file_get_contents($file), true) : [];
    }
}