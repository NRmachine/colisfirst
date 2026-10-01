<?php

use App\Services\SearchHistory;
use Predis\Client;

it('keeps the SQL search available when Redis is unavailable', function () {
    config(['search_history.enabled' => true]);
    $this->mock(SearchHistory::class, function ($mock) {
        $mock->shouldReceive('remember')->once()->andThrow(new RuntimeException('Redis indisponible'));
    });
    $this->get('/trajets?origin=Paris')->assertOk()->assertSee('Trouver un trajet');
});

it('stores reads deduplicates limits and deletes real Redis search history', function () {
    if (getenv('RUN_REDIS_TESTS') !== '1') {
        $this->markTestSkipped('RUN_REDIS_TESTS=1 active le test avec un vrai serveur Redis.');
    }
    $history = app(SearchHistory::class);
    $session = 'test-'.bin2hex(random_bytes(16));
    $other = 'test-'.bin2hex(random_bytes(16));
    $redis = new Client(['host' => config('search_history.host'), 'port' => config('search_history.port'), 'database' => config('search_history.database'), 'password' => config('search_history.password')]);
    try {
        $history->remember($other, ['origin' => 'Lyon']);
        foreach (['Paris', 'Lille', 'Nice', 'Nantes', 'Bordeaux', 'Toulouse'] as $city) {
            $history->remember($session, ['origin' => $city, 'country' => 'Algérie']);
        }
        expect($history->recent($session))->toHaveCount(5)
            ->and($history->recent($session)[0]['origin'])->toBe('Toulouse')
            ->and(array_column($history->recent($session), 'origin'))->not->toContain('Paris');
        $history->remember($session, ['country' => 'Algérie', 'origin' => 'Nice']);
        expect($history->recent($session))->toHaveCount(5)
            ->and($history->recent($session)[0]['origin'])->toBe('Nice')
            ->and($redis->type($history->key($session)))->toBe('list')
            ->and($redis->ttl($history->key($session)))->toBeGreaterThan(86390)->toBeLessThanOrEqual(86400);
        $history->clear($session);
        expect($history->recent($session))->toBe([])
            ->and($history->recent($other)[0]['origin'])->toBe('Lyon');
    } finally {
        $history->clear($session);
        $history->clear($other);
    }
});

it('shows and clears the current session history through HTTP using Redis', function () {
    if (getenv('RUN_REDIS_TESTS') !== '1') {
        $this->markTestSkipped('RUN_REDIS_TESTS=1 active le test avec un vrai serveur Redis.');
    }
    config(['search_history.enabled' => true]);
    $this->get('/trajets?origin=Paris&country=Alg%C3%A9rie')->assertOk()->assertSee('Effacer mes recherches');
    $session = session()->getId();
    $this->withCookie(config('session.cookie'), $session);
    try {
        expect(app(SearchHistory::class)->recent($session)[0]['origin'])->toBe('Paris');
        $this->delete(route('search-history.destroy'))->assertRedirect(route('trips.index'));
        expect(app(SearchHistory::class)->recent($session))->toBe([]);
        $this->get('/trajets')->assertOk()->assertDontSee('Effacer mes recherches');
    } finally {
        app(SearchHistory::class)->clear($session);
    }
});
