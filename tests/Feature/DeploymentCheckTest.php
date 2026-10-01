<?php

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->app->detectEnvironment(fn () => 'production');
    config([
        'app.debug' => false,
        'app.url' => 'https://colisfirst.example',
        'session.secure' => true,
        'app.vercel' => false,
        'marketplace.demo' => false,
        'mail.default' => 'smtp',
        'mail.mailers.smtp.username' => 'test-user',
        'mail.mailers.smtp.password' => 'test-password',
        'legal.operator' => 'Test operator',
        'legal.address' => 'Test address',
        'legal.email' => 'test@example.com',
        'legal.host' => 'Test host',
        'payments.enabled' => true,
        'payments.secret' => 'sk_test_fake',
        'payments.webhook_secret' => 'whsec_fake',
        'payments.live' => false,
    ]);
});

it('accepts test payments only with the explicit test deployment option', function () {
    Artisan::call('colisfirst:deployment-check');
    expect(Artisan::output())->toContain('À CONFIGURER | Stripe réel configuré');

    Artisan::call('colisfirst:deployment-check', ['--allow-stripe-test' => true]);
    expect(Artisan::output())->toContain('OK | Stripe test configuré (aucun paiement réel)')
        ->not->toContain('sk_test_fake')->not->toContain('whsec_fake');
});

it('does not mark missing Stripe credentials or live payments as valid test payments', function () {
    config(['payments.secret' => null]);
    Artisan::call('colisfirst:deployment-check', ['--allow-stripe-test' => true]);
    expect(Artisan::output())->toContain('À CONFIGURER | Stripe test configuré');

    config(['payments.secret' => 'sk_live_fake', 'payments.live' => true]);
    Artisan::call('colisfirst:deployment-check', ['--allow-stripe-test' => true]);
    expect(Artisan::output())->toContain('À CONFIGURER | Stripe test configuré');
});
