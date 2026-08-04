# SG Galva - ERP de Gestion de Production

Application métier sur-mesure (ERP) pour la gestion complète de la chaîne de production : Réception, Ordonnancement, Cataphorèse, Galvanisation, Colisage et Livraisons.

## 🎯 Fonctionnalités Principales

*   **Réception Terrain & Ordonnancement** : Saisie des bons de commande, création des bons de travail, priorisation et génération des Fiches de Suivi.
*   **Planning de Production** : Vue complète et modifiable par l'ordonnancement pour organiser le traitement (Grand Bain, Petit Bain).
*   **Interface Caristes** : Accès simplifié sur tablette (via Lien Magique) pour gérer les mouvements des pièces (pesée, manutention, stockage).
*   **Atelier & Cataphorèse** : Tableaux de bord spécifiques pour les Chefs d'Équipe permettant de valider les étapes de traitement.
*   **Colisage & Livraison** : Gestion des emplacements finaux, colisage, préparation des expéditions et génération des Bons de Livraison.
*   **Administration** : Tableau de bord global, suivi des performances, gestion des utilisateurs, des clients et des rôles.

## 💻 Technologies

*   **Backend :** PHP 8.2+ / Symfony 7
*   **Base de Données :** PostgreSQL
*   **Frontend :** Twig, CSS Vanilla (Design responsive optimisé desktop & tablette), JavaScript
*   **Déploiement :** Docker & Docker Compose

## 🚀 Déploiement et Installation (Serveur)

Pour les équipes informatiques chargées du déploiement en production, veuillez consulter le fichier dédié :
👉 **[INSTRUCTIONS_DEPLOIEMENT.md](INSTRUCTIONS_DEPLOIEMENT.md)**

Ce fichier contient la procédure complète pour une installation automatisée (via Docker) ou manuelle sur un serveur Windows/Linux.

## 🛠️ Développement en local

Si vous souhaitez contribuer ou modifier le code source :

1.  **Cloner le dépôt :**
    ```bash
    git clone https://github.com/AlexandreVILLANI/sg_galva.git
    cd sg_galva
    ```

2.  **Installer les dépendances PHP :**
    ```bash
    composer install
    ```

3.  **Configurer l'environnement :**
    Copiez `.env` en `.env.local` et configurez votre accès à la base de données PostgreSQL locale.

4.  **Initialiser la base de données :**
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ```
    *(Note : Les fixtures chargent l'ensemble des rôles nécessaires, le compte administrateur "alex" et les accès caristes).*

5.  **Lancer le serveur de développement :**
    ```bash
    symfony server:start
    ```

## 🔒 Sécurité et Rôles

Le système s'appuie sur une gestion fine des rôles Symfony :
- `ROLE_ADMIN`, `ROLE_ORDONNANCEMENT`, `ROLE_RECEPTION_TERRAIN`, `ROLE_CHEF_EQUIPE`, `ROLE_CHEF_CATAPHORESE`, `ROLE_PESEE`, `ROLE_COLISAGE`, `ROLE_CARISTE`, `ROLE_COMMERCIAL`.

*Certains rôles, notamment les caristes, sont configurés pour s'authentifier sans mot de passe via un système de **Lien Magique** à usage interne sur tablette.*
