# ColisFirst

Plateforme de livraison de colis entre la France et le Maghreb : recherche de trajets, réservations, suivi, dossiers et administration.

Laravel 13, PHP 8.5, Blade et React/Inertia pour les paramètres de sécurité. SQLite en local, PostgreSQL sur le VPS.

## Installation locale

Prérequis : PHP 8.5, Composer, Node.js 22.12+ et npm.

```sh
composer install
npm ci
```

Sur une nouvelle installation uniquement :

```sh
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
```

Puis :

```sh
php artisan migrate
npm run build
php artisan serve
```

Site : http://127.0.0.1:8000. Pour travailler sur les composants React, lancer `npm run dev` dans un second terminal. Cette commande démarre Vite ; elle ne déploie pas le site.

## Structure

| Dossier | Contenu |
| --- | --- |
| `app/Http/Controllers` | Actions et réponses HTTP |
| `app/Http/Requests` | Validation des formulaires |
| `app/Models` | Données et relations |
| `app/Actions`, `app/Concerns` | Authentification et règles partagées |
| `app/Console` | Commandes de maintenance |
| `routes` | Routes regroupées par fonction |
| `resources/views` | Pages Blade et éléments communs |
| `resources/js` | Paramètres et sécurité React/Inertia |
| `resources/css` | Styles compilés par Vite |
| `public/css`, `public/js`, `public/images` | Ressources des pages Blade |
| `database` | Migrations, factories et données de démonstration |
| `config` | Configuration du site |
| `tests` | Tests automatisés |
| `deploy` | Configuration Docker, Nginx, PHP et Supervisor |
| `docs` | Documentation technique et déploiement |
| `storage` | Documents privés, journaux et caches |

`vendor`, `node_modules` et `public/build` sont générés. Ne pas les modifier manuellement. Les fichiers `.env` et les données locales restent privés.

## Routes

- `web.php` : pages publiques et chargement des routes protégées.
- `account.php` : compte, dossier, support, notifications et confidentialité.
- `trips.php` : trajets et candidatures de livreurs.
- `bookings.php` : réservations, messages et paiements.
- `admin.php` : administration et validation des dossiers.
- `settings.php` : profil, mot de passe et sécurité.
- `console.php` : tâches planifiées.

## Vérifications

```sh
php artisan test
vendor/bin/pint --test
npm run types:check
npm run build
```

Les tests utilisent une base SQLite en mémoire. Les pages métier utilisent `public/css/market.css` et `public/js/market.js` ; les écrans Inertia nécessitent les assets compilés.

## Démonstration locale

Activer `MARKETPLACE_DEMO=true` dans `.env`, puis lancer `php artisan db:seed --class=MarketplaceSeeder` uniquement en local. Le seeder crée des comptes fictifs et réinitialise leurs mots de passe. Ne pas l’utiliser avec des données réelles.

## VPS

Adresse temporaire : https://colisfirst.57.131.200.114.sslip.io

Installation et sauvegardes : [procédure VPS](docs/deployment/vps.md).

Le déploiement utilise `Dockerfile`, `compose.production.yaml` et `deploy/`. Les e-mails, les paiements et les informations légales doivent être configurés et validés avant l’exploitation commerciale.
