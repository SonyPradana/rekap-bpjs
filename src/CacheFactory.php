<?php

declare(strict_types=1);

namespace App;

use System\Cache\CacheInterface;
use System\Cache\Storage\FileStorage;
use System\Cache\Storage\PdoStorage;

final class CacheFactory
{
    /**
     * Create cache storage based on configuration.
     *
     * @throws \InvalidArgumentException
     */
    public static function create(Configuration $config, string $baseDir): CacheInterface
    {
        $driver     = $config->get('cache.driver', 'file');
        $defaultTTL = $config->get('cache.ttl', 7_884_008);

        if ('file' === $driver) {
            return self::createFileDriver($defaultTTL);
        }

        if ('sqlite' === $driver) {
            $path = $baseDir . '/cache/cache.sqlite';

            return self::createPdoDriver('sqlite:' . $path, $defaultTTL);
        }

        throw new \InvalidArgumentException('Unsupported cache driver: ' . $driver);
    }

    private static function createFileDriver(int $defaultTTL): CacheInterface
    {
        return new FileStorage(
            path: $baseDir . '/cache',
            defaultTTL: $defaultTTL
        );
    }

    private static function createPdoDriver(string $dsn, int $defaultTTL): CacheInterface
    {
        $pdo  = new \PDO($dsn);
        $pdo->exec('CREATE TABLE IF NOT EXISTS cache ("key" TEXT PRIMARY KEY, "value" TEXT, "expiration" INTEGER)');

        return new PdoStorage(
            pdo: $pdo,
            defaultTTL: $defaultTTL
        );
    }
}
