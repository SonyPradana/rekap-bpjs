<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class PCare
{
    /** @var Client[] */
    private array $clients;
    private int $currentIndex = 0;

    /**
     * @param Client|Client[] $clients
     */
    public function __construct(Client|array $clients)
    {
        $this->clients = is_array($clients) ? $clients : [$clients];

        if (empty($this->clients)) {
            throw new \InvalidArgumentException('At least one client required.');
        }
    }

    private function shouldFailover(int $status): bool
    {
        return match (true) {
            $status === 401 => true, // expired
            $status === 403 => true, // unauthorized
            $status === 429 => true, // rate limited
            $status >= 500  => true, // server error
            default         => false,
        };
    }

    private function request(string $method, string|UriInterface $uri): ResponseInterface
    {
        $total    = count($this->clients);
        $attempts = 0;

        while ($attempts < $total) {
            $client             = $this->clients[$this->currentIndex];
            $this->currentIndex = ($this->currentIndex + 1) % $total;

            try {
                $response = match ($method) {
                    'get'   => $client->get($uri),
                    'post'  => $client->post($uri),
                    default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
                };

                if (false === $this->shouldFailover($response->getStatusCode())) {
                    return $response;
                }
            } catch (\InvalidArgumentException $e) {
                throw $e;
            } catch (\Throwable) {
                // connection error, timeout → failover
            }

            $attempts++;
        }

        throw new \RuntimeException('All PCare accounts are unavailable.');
    }

    public function get(string|UriInterface $uri): ResponseInterface
    {
        return $this->request('get', $uri);
    }

    public function kunjungan(string $jenis, string $date, int $start = 0, int $end = 5_000): ResponseInterface
    {
        return $this->get("/kunjungan/{$date}/{$jenis}/{$start}/{$end}");
    }

    public function nik(string $nik): ResponseInterface
    {
        return $this->get("/info/{$nik}/nik");
    }

    public function bpjs(string $bpjs): ResponseInterface
    {
        return $this->get("/info/{$bpjs}/bpjs");
    }
}
