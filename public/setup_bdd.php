<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

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

// 2. Lancer les fixtures
try {
    $input2 = new ArrayInput([
        'command' => 'doctrine:fixtures:load',
        '--no-interaction' => true,
    ]);
    $output2 = new BufferedOutput();
    $application->run($input2, $output2);
    echo "<h3>Fixtures (Comptes) :</h3><pre>" . $output2->fetch() . "</pre>";
} catch (\Exception $e) {
    echo "<h3>Erreur Fixtures :</h3><pre>" . $e->getMessage() . "</pre>";
}

echo "<h2><a href='/'>Aller sur l'application</a></h2>";
