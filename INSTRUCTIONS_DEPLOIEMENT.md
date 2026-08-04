# Guide de Déploiement (Axe Informatique)

Ce document explique comment déployer l'application sur un serveur en utilisant **Docker** et **Docker Compose**. C'est la méthode recommandée car elle configure automatiquement le serveur Web (Apache), PHP (8.2+) et la base de données (PostgreSQL 16).

## 1. Prérequis sur le serveur hôte

- Un serveur (Windows Server, Linux ou macOS)
- **Docker** et **Docker Compose** installés
- Le port `80` (HTTP) et `5432` (PostgreSQL) doivent être libres.
- *Recommandé : Un reverse proxy (Nginx, Traefik) si vous prévoyez d'ajouter le HTTPS (SSL).*

## 2. Lancement de l'application

1. Récupérez le code source de l'application (via fichier `.zip` ou clone Git) et placez-le dans un dossier sur votre serveur.
2. Ouvrez un terminal (ou PowerShell) dans ce dossier.
3. Exécutez la commande suivante pour construire et démarrer les conteneurs en tâche de fond :

```bash
docker-compose up -d --build
```

Docker va automatiquement télécharger les images nécessaires, compiler l'application PHP, installer les dépendances via Composer, et démarrer la base de données.

## 3. Initialisation de la base de données

Une fois les conteneurs démarrés (vous pouvez vérifier avec `docker ps`), il faut initialiser la structure de la base de données et charger les données de départ (rôles et utilisateurs).

Exécutez ces commandes depuis le serveur hôte (elles s'exécuteront à l'intérieur du conteneur `sg_galva_web`) :

1. Créer la structure (tables) :
```bash
docker exec -it sg_galva_web php bin/console doctrine:migrations:migrate --no-interaction
```

2. Charger les données par défaut (Rôles, Administrateur, et accès Caristes Magiques) :
*Attention : ne faites ceci qu'à la première installation, cela vide la base actuelle.*
```bash
docker exec -it sg_galva_web php bin/console doctrine:fixtures:load --no-interaction
```

## 4. C'est terminé !

L'application est maintenant accessible via un navigateur web à l'adresse IP du serveur :
`http://ADRESSE_IP_DU_SERVEUR/`

### Notes de sécurité (Production)
- Les mots de passe et configurations par défaut sont inscrits dans le fichier `docker-compose.yml`. Dans un vrai environnement de production, il est conseillé de créer un fichier `.env` à côté du `docker-compose.yml` pour surcharger les variables `APP_SECRET`, `POSTGRES_USER` et `POSTGRES_PASSWORD`.
- Assurez-vous d'implémenter un certificat SSL (HTTPS) au niveau du réseau ou du reverse proxy pour protéger les mots de passe.

---

## Alternative : Installation manuelle (Sans Docker)

Si vous n'utilisez pas Docker sur votre serveur (ex: Windows Server classique avec WampServer/IIS, ou serveur Linux standard), voici la procédure manuelle complète.

### Étape 1 : Prérequis du serveur
1. **PHP 8.2 ou supérieur** (extensions requises : pdo_pgsql, intl, mbstring, gd, zip, opcache).
2. **PostgreSQL 14, 15 ou 16** installé manuellement sur le serveur (depuis *postgresql.org*).
3. **Composer** installé sur le serveur (pour gérer les dépendances PHP).
4. Un serveur Web (**Apache** ou **IIS** ou **Nginx**) configuré pour pointer son dossier racine (`DocumentRoot`) vers le sous-dossier `/public` du projet (C'est TRÈS IMPORTANT pour la sécurité).

### Étape 2 : Préparation de l'application
1. Placez les fichiers de l'application sur le serveur (ex: `C:\wamp64\www\sg_galva` ou `/var/www/sg_galva`).
2. Ouvrez une invite de commande dans ce dossier et exécutez :
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

### Étape 3 : Configuration
1. À la racine du projet, créez un fichier `.env.local`.
2. Ajoutez-y vos identifiants de base de données PostgreSQL fraîchement installée et déclarez l'environnement de production :
   ```env
   APP_ENV=prod
   APP_SECRET=VOTRE_CLE_SECRETE_ALEATOIRE_A_GENERER_ICI
   DATABASE_URL="postgresql://NOM_UTILISATEUR:MOT_DE_PASSE@127.0.0.1:5432/NOM_DE_LA_BASE?serverVersion=16&charset=utf8"
   ```

### Étape 4 : Base de données & Cache
1. Créez la base de données :
   ```bash
   php bin/console doctrine:database:create
   ```
2. Installez les tables :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
3. Chargez les comptes de départ (Caristes, Admin, etc.) :
   ```bash
   php bin/console doctrine:fixtures:load
   ```
4. Videz le cache et préparez les fichiers :
   ```bash
   php bin/console cache:clear
   ```

L'application est désormais prête à être accédée !
