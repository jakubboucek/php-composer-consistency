<?php

declare(strict_types=1);

use JakubBoucek\ComposerConsistency\ComposerConsistency;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

/** @testCase */
class ComposerConsistencyCompareReqsTest extends TestCase
{
    private const TEMP_DIR = __DIR__ . '/temp';

    protected function setUp(): void
    {
        @mkdir(self::TEMP_DIR, 0777, true);
    }

    public function getCompareReqsData(): array
    {
        return [
            [
                ['nette/tester' => '1.23'],
                ['nette/tester' => '1.23'],
                []
            ],
            [
                ['nette/tester' => '1.23'],
                ['nette/tester' => '4.56'],
                ['nette/tester' => ['required' => '1.23', 'installed' => '4.56']]
            ],
            [
                [],
                ['nette/tester' => '4.56'],
                ['nette/tester' => ['required' => null, 'installed' => '4.56']]
            ],
            [
                ['nette/tester' => '1.23'],
                [],
                ['nette/tester' => ['required' => '1.23', 'installed' => null]]
            ]

        ];
    }

    /**
     * @param array $required
     * @param array $installed
     * @param array $result
     * @dataProvider getCompareReqsData
     */
    public function testSame(array $required, array $installed, array $result): void
    {
        $checker = new ComposerConsistency(self::TEMP_DIR);
        Assert::with(
            $checker,
            function () use ($required, $installed, $result) {
                /** @noinspection PhpUndefinedMethodInspection */
                Assert::same($result, $this->compareReqs($required, $installed));
            }
        );
    }
}

(new ComposerConsistencyCompareReqsTest())->run();
