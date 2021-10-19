#!/usr/bin/env php
<?php

use Leadvertex\Plugin\Core\Dialog\Factories\ConsoleAppFactory;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

$factory = new ConsoleAppFactory();
$application = $factory->build();
$application->run();