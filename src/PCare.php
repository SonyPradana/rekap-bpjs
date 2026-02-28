<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class PCare
{
    private array $clients;
    private int $currentIndex = 0;

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

    private function request(string $method, string $uri): ResponseInterface
    {
        $total    = count($this->clients);
        $attempts = 0;

        while ($attempts < $total) {
            $client             = $this->clients[$this->currentIndex];
            $this->currentIndex = ($this->currentIndex + 1) % $total;

            try {
                $response = $client->{$method}($uri);

                if (!$this->shouldFailover($response->getStatusCode())) {
                    return $response;
                }
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
        return $this->request('get', "/kunjungan/{$date}/{$jenis}/{$start}/{$end}");
    }

    public function nik(string $nik): ResponseInterface
    {
        return $this->request('get', "/info/{$nik}/nik");
    }

    public function bpjs(string $bpjs): ResponseInterface
    {
        return $this->request('get', "/info/{$bpjs}/bpjs");
    }
}
