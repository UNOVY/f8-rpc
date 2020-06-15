<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
    ])
    ->setFinder($finder);
