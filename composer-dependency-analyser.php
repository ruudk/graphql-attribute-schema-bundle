<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

// Fixes undefined functions
require_once __DIR__ . '/vendor/symfony/dependency-injection/Loader/Configurator/ContainerConfigurator.php';

$config = new Configuration();

return $config;
