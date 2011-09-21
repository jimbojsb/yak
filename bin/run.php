#!/usr/bin/env php
<?php
require_once __DIR__ . '/../lib/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace('Symfony', __DIR__ . '/../lib');
$loader->registerNamespace('Hegira', __DIR__ . '/../lib');
$loader->register();

$app = new Hegira\Application\Hegira();
$app->run();