<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    // Adresse de l'API Django (à remplacer par l'IP de la machine du groupe Django)
    private const BASE_URL = 'http://127.0.0.1:8000';

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(bool:API_MOCK)%')] private bool $mock,
        #[Autowire('%kernel.project_dir%/Mock')] private string $mockDir,
    ) {}

    // Le login passe toujours par la vraie API, même quand API_MOCK=1
    public function login(string $email, string $password): array
    {
        return $this->decode($this->client->request('POST', self::BASE_URL.'/v1/login', [
            'json' => ['email' => $email, 'mdp' => $password],
        ]));
    }

    public function get(string $path): array
    {
        if ($this->mock) return $this->readMock('get', $path);
        return $this->decode($this->client->request('GET', self::BASE_URL.$path));
    }

    public function post(string $path, array $data): array
    {
        if ($this->mock) return $this->readMock('post', $path);
        return $this->decode($this->client->request('POST', self::BASE_URL.$path, ['json' => $data]));
    }

    public function put(string $path, array $data): array
    {
        if ($this->mock) return $this->readMock('put', $path);
        return $this->decode($this->client->request('PUT', self::BASE_URL.$path, ['json' => $data]));
    }

    public function delete(string $path): array
    {
        if ($this->mock) return $this->readMock('delete', $path);
        return $this->decode($this->client->request('DELETE', self::BASE_URL.$path));
    }

    // Lève une exception si statut >= 400 ; un 204 / corps vide renvoie []
    private function decode(ResponseInterface $response): array
    {
        if ($response->getStatusCode() === 204 || $response->getContent() === '') return [];
        return $response->toArray();
    }

    // "/v1/menus/1" → Mock/get_v1_menus_1.json
    private function readMock(string $method, string $path): array
    {
        $file = $this->mockDir.'/'.$method.'_'.str_replace('/', '_', trim($path, '/')).'.json';
        return is_file($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    }
}
