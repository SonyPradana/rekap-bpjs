<?php

declare(strict_types=1);

namespace App;

final class Configuration
{
    public function __construct(
        private array $config,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (false === strpos($key, '.')) {
            return $this->config[$key] ?? $default;
        }

        $keys  = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (false === is_array($value) || false === array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
