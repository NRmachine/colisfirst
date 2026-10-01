# Déploiement de ColisFirst sur Vercel

## État constaté le 1er octobre 2026

La connexion du CLI Vercel est valide. La commande `vercel project add colisfirst` échoue avant la création du projet avec le message :

> Your Team exceeded our fair use limits and has been blocked.

L'équipe concernée est `kachas-projects-42b467e7`. Aucun site ColisFirst n'a été déployé et aucune URL publique n'est disponible. Le résultat est conservé dans `docs/dwwm/evidence/vercel-deployment.txt`. Faire lever la restriction auprès de Vercel depuis le tableau de bord/support avant de reprendre. Les autres projets du compte n'ont pas été modifiés.

## Configuration ajoutée

- `vercel.json` : exécution PHP 8.5 via `vercel-php@0.9.0`, région Paris, fichiers publics explicitement sélectionnés, routes Laravel.
- `api/index.php` : point d'entrée Laravel, HTTPS sur Vercel et création du répertoire temporaire des vues compilées.
- `.vercelignore` : exclusion des secrets, données SQLite, justificatifs, journaux, comptes de test et du marqueur de serveur Vite local. Les fichiers compilés `public/build` restent inclus.
- `PRIVATE_DOCUMENT_DISK` : sélection du stockage privé, utilisée pour le dépôt, le téléchargement, le remplacement et la purge. Le pilote S3 est installé ; la valeur locale reste inchangée.
- La connexion PostgreSQL accepte `DATABASE_URL`, fournie notamment par Neon. Les autres bases gardent leur configuration existante.

La configuration Vercel reste à valider sur le service : le blocage empêche tout build distant. La recette locale du stockage utilise un disque simulé ; elle ne prouve pas la connexion à un bucket réel.

## Reprise après déblocage

1. Depuis le dossier Laravel `colisfirst/`, créer le projet `colisfirst`, le lier avec `vercel link` et sélectionner Node.js 22.x dans ses paramètres. Ne pas déployer le dossier parent contenant le livret.
2. Créer une base PostgreSQL distincte pour ColisFirst, située en Europe si possible. Ne pas réutiliser la base du projet Achathub. Les migrations PostgreSQL restent à exécuter et à vérifier sur cette base neuve ; la recette actuelle utilise SQLite.
3. Configurer un bucket S3 privé avec accès limité au projet. Aucun accès public, aucune URL publique de justificatif. Renseigner les variables de `vercel.env.example` dans Vercel, ainsi que les informations `LEGAL_*`. Ne pas déposer de pièces réelles avant validation du stockage et des droits.
4. Générer une nouvelle clé avec `php artisan key:generate --show` et la saisir dans `APP_KEY` du nouvel environnement. Garder cette valeur secrète et stable ensuite. Renseigner l'adresse HTTPS exacte dans `APP_URL`.
5. Préparer les assets localement : `npm ci` puis `npm run build`. Vérifier `public/build/manifest.json`. Les builders Vercel téléversent les assets précompilés ; ils ne remplacent pas cette étape.
6. Dans un environnement isolé possédant seulement la configuration de la base cible, lancer `php artisan migrate --force`. Ne pas lancer `db:seed` : les mots de passe connus et données fictives du poste de développement ne sont pas destinés au site public. Prévoir un administrateur avec un mot de passe inédit.
7. Exécuter `vercel` pour obtenir une prévisualisation protégée et tester cette URL. Après validation, utiliser `vercel --prod`. Conserver l'URL et le journal réel du déploiement dans les preuves.

Les caches de vues et les manifestes Laravel vont dans `/tmp`. Ce répertoire est éphémère et ne doit contenir ni base SQLite ni justificatif utilisateur. Les sessions et le cache applicatif utilisent la base distante. Les notifications sont synchrones (`QUEUE_CONNECTION=sync`) pour éviter une file sans worker ; le fournisseur SMTP et sa réception doivent être testés. La purge planifiée nécessite un ordonnanceur externe authentifié ou un worker dédié : aucun cron Vercel n'est encore configuré.

Les fonctions Vercel ont une limite de charge utile : vérifier les dépôts de justificatifs proches de la limite du fournisseur. L'application accepte actuellement 5 Mo par fichier ; ce seuil n'est pas une garantie de compatibilité avec le proxy Vercel. Prévoir une limite adaptée ou un dépôt direct privé signé avant ouverture.

## Recette distante à effectuer

Vérifier `/up`, `/`, `/trajets`, `/login` et `/register`, les CSS et le logo ; créer un compte de recette avec des coordonnées contrôlées ; vérifier la réception de l'e-mail, la persistance des données entre requêtes et après redéploiement, et les cookies HTTPS. Déposer une pièce fictive, la remplacer, puis contrôler que le propriétaire peut la télécharger et qu'un autre compte reçoit 403. Vérifier que `.env`, la base SQLite et les fichiers de `storage` ne sont jamais servis. Tester les rôles et la réservation, puis les sauvegardes et leur restauration.

Le paiement reste désactivé. Une URL accessible ne valide ni les paiements réels, ni SMTP, ni le fonctionnement complet du service. `php artisan colisfirst:deployment-check` distingue les prérequis encore absents et n'affiche aucun secret.

## Références techniques

- [Runtime PHP communautaire Vercel](https://github.com/vercel-community/php)
- [Stockage Vercel](https://vercel.com/docs/storage)
- [Limites d'utilisation Vercel](https://vercel.com/docs/limits/fair-use-guidelines)
