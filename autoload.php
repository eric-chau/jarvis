<?php

$autoloadFile = __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

if (!is_readable($autoloadFile)) {
    throw new \LogicException('autoloader cannot be find in vendor/. Did you run "composer install --no-dev"?');
}

require_once $autoloadFile;

if (class_exists('Jarvis')) {
    throw new \Exception('Jarvis class already exist!');
}

require_once __DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Jarvis.php';
