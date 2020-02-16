<?php

use JakubBoucek\ComposerConsistency\Checker;
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();

// protected methos -> public method
$method = (new ReflectionClass(Checker::class))->getMethod('compareReqs');
$method->setAccessible(true);

$o = new Checker(__DIR__ . '/..');

Assert::same(
    [],
    $method->invokeArgs(
        $o,
        [
            ['nette/tester' => '1.23'],
            ['nette/tester' => '1.23']
        ]
    )
);

Assert::same(
    ['nette/tester' => ['required' => '1.23', 'installed' => '4.56']],
    $method->invokeArgs(
        $o,
        [
            ['nette/tester' => '1.23'],
            ['nette/tester' => '4.56']
        ]
    )
);

Assert::same(
    ['nette/tester' => ['required' => null, 'installed' => '4.56']],
    $method->invokeArgs(
        $o,
        [
            [],
            ['nette/tester' => '4.56']
        ]
    )
);

Assert::same(
    ['nette/tester' => ['required' => '1.23', 'installed' => null]],
    $method->invokeArgs(
        $o,
        [
            ['nette/tester' => '1.23'],
            []
        ]
    )
);
