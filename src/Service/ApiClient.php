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

    // "/v1/menus/1" → mock/get_v1_menus_1.json
    private function readMock(string $method, string $path): array
    {
        $file = $this->mockDir.'/'.$method.'_'.str_replace('/', '_', trim($path, '/')).'.json';
        return is_file($file) ? json_decode(file_get_contents($file), true) : [];
    }
}