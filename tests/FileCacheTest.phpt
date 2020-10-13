<?php

declare(strict_types=1);

use JakubBoucek\ComposerConsistency\Cache\FileCache;
use Tester\Assert;
use Tester\Environment;
use Tester\Helpers;
use Tester\TestCase;

require __DIR__ . '/../vendor/autoload.php';

Environment::setup();

/** @testCase */
class FileCacheTest extends TestCase
{
    private const TEMP_DIR = __DIR__ . '/temp/cache';
    private const LOCK_DIR = __DIR__ . '/lock';

    protected function setUp(): void
    {
        @mkdir(self::LOCK_DIR, 0777, true);
        @mkdir(self::TEMP_DIR, 0777, true);
        Environment::lock('cache', self::LOCK_DIR);
        Helpers::purge(self::TEMP_DIR);
    }

    protected function getDataKeysValues(): array
    {
        return [
            ['foo', 'bar', 'kopo'],
            [123, 456, 789],
            [new stdClass(), [true], null],
        ];
    }

    /**
     * @var mixed $value
     * @var mixed $key
     * @dataProvider getDataKeysValues
     */
    public function testCacheMatchContent($key, $value): void
    {
        $cache = new FileCache(self::TEMP_DIR);

        $cache->write($key, $value);
        Assert::true($cache->isValid($key, $value));
    }

    /**
     * @var mixed $key
     * @var mixed $value
     * @var mixed $invalidValue
     * @dataProvider getDataKeysValues
     */
    public function testCacheUnmatchContent($key, $value, $invalidValue): void
    {
        $cache = new FileCache(self::TEMP_DIR);

        $cache->write($key, $value);
        Assert::false($cache->isValid($key, $invalidValue));
    }

    /**
     * @var mixed $key
     * @var mixed $value
     * @dataProvider getDataKeysValues
     */
    public function testCacheCheckExists($key, $value): void
    {
        $cache = new FileCache(self::TEMP_DIR);

        $cache->write($key, $value);
        Assert::true($cache->isExists($key));
    }

    /**
     * @var mixed $key
     * @var mixed $value
     * @var mixed $invalidKey
     * @dataProvider getDataKeysValues
     */
    public function testCacheCheckNotExists($key, $value, $invalidKey): void
    {
        $cache = new FileCache(self::TEMP_DIR);

        $cache->write($key, $value);
        Assert::false($cache->isExists($invalidKey));
    }

    /**
     * @var mixed $key
     * @var mixed $value
     * @var mixed $invalidKey
     * @dataProvider getDataKeysValues
     */
    public function testCacheCheckInvalidate($key, $value): void
    {
        $cache = new FileCache(self::TEMP_DIR);

        $cache->write($key, $value);
        $cache->invalidate($key);
        Assert::false($cache->isExists($key));
    }

    /**
     * @var mixed $key
     * @var mixed $value
     * @var mixed $invalidKey
     * @dataProvider getDataKeysValues
     */
    public function testCacheCheckInvalidateOther($key, $value, $invalidKey): void
    {
        $cache = new FileCache(self::TEMP_DIR);

        $cache->write($key, $value);
        $cache->invalidate($invalidKey);
        Assert::true($cache->isExists($key));
    }
}

(new FileCacheTest())->run();
