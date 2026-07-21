```plaintext

sg_galva/
├── assets/                 # Ressources Front-end (AssetMapper)
│   ├── controllers/        # Contrôleurs JavaScript (Stimulus) : interactivité tactile
│   └── styles/             # Feuilles de style CSS (Design mobile-first)
├── public/                 # Point d'entrée public du serveur
│   ├── index.php           # Contrôleur frontal (initialisation du Kernel)
│   └── uploads/            # Stockage des photos de déchargement et PDF générés
├── src/                    # Cœur de l'application (Code PHP)
│   ├── Command/            # Scripts de maintenance (Nettoyage photos, Import clients)
│   ├── Controller/         # Logique de décision (Actions de l'application)
│   ├── Entity/             # Modélisation des données (Classes PHP / PostgreSQL)
│   ├── Form/               # Définition des formulaires (simples et imbriqués)
│   ├── Repository/         # Couche d'accès aux données (Requêtes QueryBuilder)
│   └── Security/           # Gestion de l'authentification et des droits (Voters)
├── templates/              # Vues et interfaces utilisateur (Moteur Twig)
│   ├── base.html.twig      # Squelette global de l'application
│   ├── components/         # Composants réutilisables (Profils, Boutons)
│   ├── formulaire/         # Interfaces de saisie de déchargement
│   ├── planning/           # Vue temps réel de la production en atelier
│   └── bon_livraison/      # Génération et signature des documents d'expédition
├── composer.json           # Dépendances du projet et métadonnées
└── importmap.php           # Configuration du chargement JS sans compilation