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
