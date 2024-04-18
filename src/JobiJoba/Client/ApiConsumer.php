<?php

namespace App\JobiJoba\Client;

use Exception;
use HttpResponseException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiConsumer
{
    private const BASE_URL = 'https://api.jobijoba.com/v3/fr/';

    private const DEFAULT_WHAT = '';

    private const DEFALUT_WHERE = 'Bordeaux';

    private FilesystemAdapter $cache;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ParameterBagInterface $config,
        private readonly LoggerInterface $logger
    ) {
        $this->cache = new FilesystemAdapter();
    }

    private function authenticate(): string
    {
        try {
            return $this->cache->get('jobijobi_token', function (ItemInterface $item): string {
                $item->expiresAfter(3600);

                $response = $this->client->request('POST', self::BASE_URL . 'login', [
                    'json' => [
                        'client_id' => $this->config->get('jobijoba')['client_id'],
                        'client_secret' => $this->config->get('jobijoba')['client_secret']
                    ]
                ]);

                if ($response->getStatusCode() === Response::HTTP_OK) {
                    return $response->toArray()['token'];
                }

                throw new HttpResponseException($response->getContent(), $response->getStatusCode());
            });
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());

            return '';
        }
    }

    public function search(int $page): array
    {
        $cacheKey = sprintf('jobijoba_search_%s', $page);

        try {
            $jobs = $this->cache->getItem($cacheKey);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());

            return [];
        }

        if (!$jobs->isHit()) {
            $token = $this->authenticate();

            try {
                $response = $this->client->request('GET', self::BASE_URL . 'ads/search', [
                    'query' => [
                        'what' => self::DEFAULT_WHAT,
                        'where' => self::DEFALUT_WHERE,
                        'limit' => 10,
                        'page' => $page
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);

                if ($response->getStatusCode() === Response::HTTP_OK) {
                    $jobs->expiresAfter(86400);
                    $jobs->set($response->toArray()['data']);
                    $this->cache->save($jobs);
                }

                throw new HttpException($response->getStatusCode(), $response->getContent());
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $this->logger->error($e->getTraceAsString());
                return [];
            }
        }

        return $jobs->get();
    }
}
