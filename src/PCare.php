<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class PCare
{
    public function __construct(
        private Client $client,
    ) {
    }

    public function get(string|UriInterface $uri): ResponseInterface
    {
        return $this->client->get($uri);
    }

    public function kunjungan(string $jenis, string $date, int $start = 0, int $end = 5_000): ResponseInterface
    {
        return $this->client->get("/kunjungan/{$date}/{$jenis}/{$start}/{$end}");
    }

    public function nik(string $nik): ResponseInterface
    {
        return $this->client->get("/info/{$nik}/nik");
    }

    public function bpjs(string $bpjs): ResponseInterface
    {
        return $this->client->get("/info/{$bpjs}/bpjs");
    }
}
