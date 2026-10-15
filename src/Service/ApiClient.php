<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(API_BASE_URL)%')] private string $baseUrl,
        #[Autowire('%env(bool:API_MOCK)%')] private bool $mock,
        #[Autowire('%kernel.project_dir%/Mock')] private string $mockDir,
    ) {}

    public function get(string $path): array
    {
        if ($this->mock) return $this->readMock('get', $path);
        return $this->decode($this->client->request('GET', $this->baseUrl.$path));
    }

    public function post(string $path, array $data): array
    {
        if ($this->mock) return $this->readMock('post', $path);
        return $this->decode($this->client->request('POST', $this->baseUrl.$path, ['json' => $data]));
    }

    public function put(string $path, array $data): array
    {
        if ($this->mock) return $this->readMock('put', $path);
        return $this->decode($this->client->request('PUT', $this->baseUrl.$path, ['json' => $data]));
    }

    public function delete(string $path): array
    {
        if ($this->mock) return $this->readMock('delete', $path);
        return $this->decode($this->client->request('DELETE', $this->baseUrl.$path));
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
