<?php

namespace App\Console\Commands;

use App\Services\StripePayments;
use Illuminate\Console\Command;

class DeploymentCheck extends Command
{
    protected $signature = 'colisfirst:deployment-check {--allow-stripe-test : Accepte une intégration Stripe configurée en mode test}';

    protected $description = 'Vérifie les prérequis de configuration sans afficher de secret';

    public function handle(): int
    {
        $testPayments = (bool) $this->option('allow-stripe-test');

        $checks = [
            'Environnement production' => app()->isProduction(),
            'Débogage désactivé' => ! config('app.debug'),
            'URL HTTPS' => str_starts_with(config('app.url'), 'https://'),
            'Cookies de session sécurisés' => (bool) config('session.secure'),
            'Clé applicative présente' => filled(config('app.key')),
            'Base durable sur Vercel' => ! config('app.vercel') || in_array(config('database.default'), ['pgsql', 'mysql', 'mariadb'], true),
            'Documents durables sur Vercel' => ! config('app.vercel') || config('filesystems.private_documents') === 's3',
            'Assets compilés' => is_file(public_path('build/manifest.json')),
            'Mode démonstration désactivé' => ! config('marketplace.demo'),
            'SMTP configuré' => config('mail.default') === 'smtp' && filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password')),
            $testPayments ? 'Stripe test configuré (aucun paiement réel)' : 'Stripe réel configuré' => app(StripePayments::class)->configured() && ($testPayments ? ! config('payments.live') : config('payments.live')),
            'Identité exploitant et hébergeur renseignée' => filled(config('legal.operator')) && filled(config('legal.address')) && filled(config('legal.email')) && filled(config('legal.host')),
        ];
        foreach ($checks as $label => $ok) {
            $this->line(($ok ? 'OK' : 'À CONFIGURER').' | '.$label);
        }
        $this->line('Ce contrôle ne teste ni la délivrabilité e-mail, ni les paiements, ni la conformité juridique, ni les sauvegardes ou la disponibilité du serveur.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
