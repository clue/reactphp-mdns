<?php

require __DIR__ . '/../vendor/autoload.php';

$name = isset($argv[1]) ? $argv[1] : 'me.local';

$factory = new Clue\React\Mdns\Factory();
$mdns = $factory->createResolver();

$mdns->resolve($name)->then('e', 'e');

function e($v) {
    echo $v . PHP_EOL;
}

$loop->run();
