<?php declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->exclude([
        'tests/data',
        'playground',
    ])
    ->in(__DIR__);

$config = (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        // rules: https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules/index.rst
        // ruleSets: https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/ruleSets/index.rst
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'declare_strict_types' => true,
        'blank_line_after_opening_tag' => false,
        'linebreak_after_opening_tag' => false,
    ])
    ->setFinder($finder);
