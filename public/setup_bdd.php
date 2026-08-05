<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require dirname(__DIR__).'/vendor/autoload.php';

// Création du noyau Symfony
$env = $_SERVER['APP_ENV'] ?? 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? false);
$kernel = new Kernel($env, $debug);

$application = new Application($kernel);
$application->setAutoExit(false);

echo "<h1>Initialisation de la base de données...</h1>";

// 1. Lancer les migrations
try {
    $input = new ArrayInput([
        'command' => 'doctrine:migrations:migrate',
        '--no-interaction' => true,
        '--allow-no-migration' => true,
    ]);
    $output = new BufferedOutput();
    $application->run($input, $output);
    echo "<h3>Migrations (Tables) :</h3><pre>" . $output->fetch() . "</pre>";
} catch (\Exception $e) {
    echo "<h3>Erreur Migrations :</h3><pre>" . $e->getMessage() . "</pre>";
}

// 2. Création de l'utilisateur Admin en SQL direct (car doctrine:fixtures n'est pas dispo en prod)
try {
    $dbUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
    if ($dbUrl) {
        $dbUrl = str_replace('postgresql://', 'pgsql:host=', $dbUrl);
        $parsed = parse_url($_SERVER['DATABASE_URL']);
        
        $host = $parsed['host'];
        $port = $parsed['port'] ?? 5432;
        $dbname = ltrim($parsed['path'], '/');
        $user = $parsed['user'];
        $pass = $parsed['pass'];
        
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname);
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $stmt = $pdo->prepare('SELECT id FROM "user" WHERE username = :username');
        $stmt->execute(['username' => 'admin']);
        
        if (!$stmt->fetch()) {
            $hash = password_hash('admin', PASSWORD_BCRYPT);
            $roles = json_encode(['ROLE_ADMIN']);
            
            $insert = $pdo->prepare('INSERT INTO "user" (username, roles, password, prenom, type_acces) VALUES (:username, :roles, :password, :prenom, :type_acces)');
            $insert->execute([
                'username' => 'admin',
                'roles' => $roles,
                'password' => $hash,
                'prenom' => 'Admin',
                'type_acces' => 'Administrateur'
            ]);
            echo "<h3>Comptes :</h3><pre>Compte Administrateur créé avec succès (admin / admin)</pre>";
        } else {
            echo "<h3>Comptes :</h3><pre>Le compte Administrateur existe déjà.</pre>";
        }
    } else {
        echo "<h3>Erreur :</h3><pre>DATABASE_URL non trouvée.</pre>";
    }
} catch (\Exception $e) {
    echo "<h3>Erreur Création Compte :</h3><pre>" . $e->getMessage() . "</pre>";
}

echo "<h2><a href='/'>Aller sur l'application</a></h2>";
