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

    /** @var array<int, int> */
    private array $failures = [];
    /** @var array<int, float> */
    private array $openUntil = [];

    private int $failureThreshold;
    private int $cooldownSeconds;

    /**
     * @param Client|Client[] $clients
     */
    public function __construct(
        Client|array $clients,
        int $failureThreshold = 3,
        int $cooldownSeconds  = 60,
    ) {
        $this->clients          = is_array($clients) ? $clients : [$clients];
        $this->failureThreshold = $failureThreshold;
        $this->cooldownSeconds  = $cooldownSeconds;

        if (empty($this->clients)) {
            throw new \InvalidArgumentException('At least one client required.');
        }
    }

    private function shouldFailover(int $status): bool
    {
        return match (true) {
            $status === 401 => true,
            $status === 403 => true,
            $status === 429 => true,
            $status >= 500  => true,
            default         => false,
        };
    }

    private function isCircuitOpen(int $index): bool
    {
        if (false === isset($this->openUntil[$index])) {
            return false;
        }

        if (microtime(true) >= $this->openUntil[$index]) {
            unset($this->openUntil[$index], $this->failures[$index]);

            return false;
        }

        return true;
    }

    private function recordFailure(int $index): void
    {
        $this->failures[$index] = ($this->failures[$index] ?? 0) + 1;

        if ($this->failures[$index] >= $this->failureThreshold) {
            $this->openUntil[$index] = microtime(true) + $this->cooldownSeconds;
        }
    }

    private function recordSuccess(int $index): void
    {
        unset($this->failures[$index], $this->openUntil[$index]);
    }

    private function request(string $method, string|UriInterface $uri): ResponseInterface
    {
        $total    = count($this->clients);
        $attempts = 0;

        while ($attempts < $total) {
            $index              = $this->currentIndex;
            $this->currentIndex = ($this->currentIndex + 1) % $total;

            if ($this->isCircuitOpen($index)) {
                $attempts++;
                continue;
            }

            try {
                $response = match ($method) {
                    'get'   => $this->clients[$index]->get($uri),
                    'post'  => $this->clients[$index]->post($uri),
                    default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
                };

                if (false === $this->shouldFailover($response->getStatusCode())) {
                    $this->recordSuccess($index);

                    return $response;
                }

                $this->recordFailure($index);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            } catch (\Throwable) {
                $this->recordFailure($index);
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
