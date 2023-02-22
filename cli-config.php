<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "vendor/autoload.php";

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . "/src/orm"),
    $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);


// the connection configuration
$conn = array('host'     => getenv('MARIADB_HOST'),
    'driver'   => 'pdo_mysql',
    'user'     => getenv('MARIADB_USER'),
    'password' => getenv('MARIADB_PASSWORD'),
    'dbname'   => getenv('MARIADB_DATABASE'),
);


// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);

return ConsoleRunner::createHelperSet($entityManager);