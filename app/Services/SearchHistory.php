<?php

namespace App\Services;

use Predis\Client;
use Predis\Transaction\MultiExec;

class SearchHistory
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => config('search_history.host'),
            'port' => config('search_history.port'),
            'password' => config('search_history.password'),
            'database' => config('search_history.database'),
            'timeout' => 1,
            'read_write_timeout' => 1,
        ]);
    }

    public function key(string $sessionId): string
    {
        return 'colisfirst:search-history:'.hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    /** @param array<string, string|null> $filters */
    public function remember(string $sessionId, array $filters): void
    {
        $filters = array_intersect_key($filters, array_flip(['origin', 'country', 'date', 'sort']));
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        if (empty($filters['origin']) && empty($filters['country']) && empty($filters['date'])) {
            return;
        }
        ksort($filters);
        $value = json_encode($filters, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $key = $this->key($sessionId);
        $this->redis->transaction(function (MultiExec $tx) use ($key, $value): void {
            $tx->lrem($key, 0, $value);
            $tx->lpush($key, [$value]);
            $tx->ltrim($key, 0, 4);
            $tx->expire($key, 86400);
        });
    }

    /** @return list<array<string, string>> */
    public function recent(string $sessionId): array
    {
        $searches = [];
        foreach ($this->redis->lrange($this->key($sessionId), 0, 4) as $value) {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new \UnexpectedValueException('Historique de recherche invalide.');
            }
            $filters = [];
            foreach ($decoded as $name => $filter) {
                if (! is_string($name) || ! is_string($filter)) {
                    throw new \UnexpectedValueException('Filtre de recherche invalide.');
                }
                $filters[$name] = $filter;
            }
            $searches[] = $filters;
        }

        return $searches;
    }

    public function clear(string $sessionId): void
    {
        $this->redis->del([$this->key($sessionId)]);
    }
}
