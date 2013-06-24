#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use RoSk0\WDH\Command\CreateCommand;
use RoSk0\WDH\Command\DeleteCommand;


$application = new Application('WEB developer helper(WDH)', '1.0beta');
$application->add(new CreateCommand());
$application->add(new DeleteCommand());
$application->run();
