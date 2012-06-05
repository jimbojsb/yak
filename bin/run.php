#!/usr/bin/env php
<?php
require_once __DIR__ . '/../lib/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace('Symfony', __DIR__ . '/../lib');
$loader->registerNamespace('Yak', __DIR__ . '/../lib');
$loader->register();

$app = new Yak\Application();
$app->run();