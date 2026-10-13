# ColisFirst sur le VPS OVH

Adresse temporaire : https://colisfirst.57.131.200.114.sslip.io

Serveur : `ubuntu@57.131.200.114`. Sources : `/srv/colisfirst`.
Compose : `compose.production.yaml`, projet `colisfirst-production`.
Application exposée uniquement sur `127.0.0.1:8103`, derrière le Caddy partagé dans `/srv/adaptiserv-edge`.
PostgreSQL 17 dédié, sans port public. Volumes persistants pour la base et le stockage.

## Mise à jour

Sauvegarder la base et l’image actuelle avant transfert. Envoyer uniquement le code : exclure `.env*`, `.git`, `vendor`, `node_modules`, `storage`, bases locales et caches de compilation. Ne pas remplacer les variables privées du serveur.

```sh
cd /srv/colisfirst
sudo docker compose -f compose.production.yaml build
sudo docker compose -f compose.production.yaml up -d
sudo docker compose -f compose.production.yaml ps
```

Le démarrage exécute les migrations automatiquement : vérifier leur impact avant chaque mise à jour. Ne jamais utiliser `down -v` pour une mise à jour.

## Sauvegarde

```sh
cd /srv/colisfirst
mkdir -p backups
chmod 700 backups
sudo docker compose -f compose.production.yaml exec -T database pg_dump -U colisfirst colisfirst > backups/database.sql
chmod 600 backups/database.sql
```

Sauvegarder aussi le volume `colisfirst-production_colisfirst_storage` et conserver une copie hors VPS.

## Configuration à compléter

Base initialement neuve, aucune donnée locale importée. Stripe et paiements de démonstration désactivés. E-mails configurés en journal uniquement : renseigner SMTP et les coordonnées légales avant exploitation réelle. Clés générées sur le VPS, dans `.env.production` et `.env.database`, permissions 600.
Le domaine temporaire dépend du service DNS sslip.io. HTTPS géré par Caddy ; remplacer l’adresse APP_URL et le bloc Caddy lors du passage au domaine définitif. Ne pas modifier les blocs Phone Life et ACHATHUB.
