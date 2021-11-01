<?php
// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "vendor/autoload.php";

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"),
    $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);


// the connection configuration
$conn = array(
    'host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'my-secret-pw',
    'dbname'   => 'coar_inbox',
);


// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);