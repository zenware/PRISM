<?php

declare(strict_types=1);

// Register Composer Generated Autoloaders
require_once 'vendor/autoload.php';
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'PHPInSimMod.php');

$PRISM = new \PRISM\PHPInSimMod();
$PRISM->initialise($argc, $argv);
$PRISM->start();
