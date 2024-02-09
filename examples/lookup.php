<?php

require __DIR__ . '/../vendor/autoload.php';

$name = isset($argv[1]) ? $argv[1] : 'me.local';

$factory = new Clue\React\Mdns\Factory();
$mdns = $factory->createResolver();

$mdns->resolve($name)->then(function ($ip) {
    echo 'Found: ' . $ip . PHP_EOL;
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
