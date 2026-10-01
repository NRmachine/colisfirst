# Déploiement de ColisFirst avec Stripe en test

Le Dockerfile exécute Laravel avec PHP 8.5, PHP-FPM et Nginx. Il compile les assets, installe uniquement les dépendances PHP de production et exclut les secrets, la base locale, les documents et les journaux du contexte de build. Supervisor lance également le worker de notifications et le planificateur Laravel.

## État de préparation

La préparation des fichiers ne constitue pas une mise en ligne. Le compte d'hébergement, l'URL HTTPS, les identifiants SMTP, les informations de l'exploitant et les identifiants Stripe de test doivent être renseignés avant validation complète. Ne pas remplacer ces données par des valeurs fictives pour faire passer le diagnostic.

## Hébergement compatible

Railway ou Render peuvent exécuter ce Dockerfile. Netlify n'offre pas un serveur PHP Laravel standard pour cette application. Choisir une région européenne, une seule instance, un volume persistant monté exactement sur `/var/www/html/storage` et un domaine HTTPS. Le port interne par défaut est `8080`; la variable `PORT` du fournisseur est également acceptée. La route de santé est `/up`.

- Railway : https://docs.railway.com/guides/laravel
- Render Docker : https://render.com/docs/docker
- Render stockage persistant : https://render.com/docs/disks

La configuration SQLite convient à une seule instance. Ne pas déployer sans volume : la base, les documents privés et les pièces jointes doivent survivre aux redémarrages. Pour plusieurs instances, utiliser une base gérée et un stockage objet privé, avec une configuration distincte.

Un `render.yaml` est préparé pour une instance `0.5c-512mb` à Francfort avec un disque de 1 Go. Ce fichier n'a pas été appliqué : il correspond à des ressources payantes dont le budget doit être confirmé. L'URL fournie par Render est injectée automatiquement dans `APP_URL`; aucun nom de domaine ne doit être inventé. Les informations privées sont demandées lors de la création du Blueprint. Les autres champs légaux applicables restent à compléter dans le tableau de bord.

## Variables privées

`deploy/production.env.example` contient les variables à saisir dans le gestionnaire de secrets de l'hébergeur. Le fichier local `.env.production`, ignoré par Git et Docker, sert de préparation privée. La configuration `.env` de développement reste indépendante.

1. Conserver une clé `APP_KEY` stable et privée. Ne jamais la régénérer au redémarrage.
2. Renseigner `APP_URL` avec la véritable URL HTTPS. Le fournisseur doit terminer TLS et rediriger HTTP vers HTTPS.
3. `TRUSTED_PROXIES=*` s'utilise seulement derrière le proxy de l'hébergeur, lorsque le conteneur n'est pas directement accessible sur Internet. Sur un VPS, renseigner les adresses du proxy si nécessaire.
4. Renseigner l'hôte SMTP, l'utilisateur, le mot de passe et une adresse d'expédition autorisée. Pour le port 587, `MAIL_SCHEME=smtp` utilise STARTTLS lorsque le serveur le propose; pour le port 465, utiliser `smtps`. Un test d'envoi et la réception effective restent nécessaires.
5. Compléter les variables `LEGAL_*` avec les informations exactes de l'exploitant et de l'hébergeur sélectionné. Ne pas publier une adresse personnelle par supposition.
6. Garder `STRIPE_LIVE=false`. La configuration locale n'avait pas de clé de test : conserver `STRIPE_ENABLED=false` jusqu'à saisie de la clé de test et du secret du webhook de cet hébergement. Activer ensuite `STRIPE_ENABLED=true`. Aucune clé live n'est requise pour ce déploiement.

## Démarrage

Sur l'hébergeur : construire depuis le Dockerfile à la racine, configurer les variables et le volume, puis lancer le service. Le script de démarrage refuse une configuration non-production, le débogage, les cookies non sécurisés, les paiements live et le mode démonstration. Il applique les migrations sans seeder, puis prépare les caches Laravel.

Sur un serveur Docker derrière un reverse proxy HTTPS :

```sh
docker compose -f compose.production.yaml up -d --build
docker compose -f compose.production.yaml exec app php artisan colisfirst:deployment-check --allow-stripe-test
```

Ce lancement n'importe pas la base locale et ne crée pas les comptes de démonstration. Prévoir le premier compte administrateur de manière privée avant l'ouverture. Ne pas utiliser le seeder de démonstration en production.

## Vérifications après mise en ligne

- Accueil, recherche, connexion, inscription et liens de confirmation accessibles en HTTPS.
- Cookies portant Secure, HttpOnly et SameSite=Lax; aucune trace de débogage publique.
- Worker de notifications et planificateur en fonctionnement.
- Réception réelle d'un email de vérification et d'un email de réinitialisation sur une adresse de test autorisée.
- Paiement Stripe de test et webhook signé validés, sans transaction réelle.
- Base et documents toujours présents après redéploiement.
- Mentions légales visibles et exactes.
- Sauvegarde cohérente de SQLite par son API de backup et copie protégée des documents, puis essai de restauration. Un simple instantané disque n'est pas une validation de restauration.

Le diagnostic vérifie des paramètres déclarés, pas la délivrabilité, la conformité juridique ou les sauvegardes.
