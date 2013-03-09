<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RoSk0\WDH\Command\CreateCommand;
use Symfony\Component\Console\Application;


$application = new Application();
$application->add(new CreateCommand());
$application->run();
