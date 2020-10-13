<?php

declare(strict_types=1);

namespace JakubBoucek\ComposerConsistency\Cache;

class FileCache
{
    private const CACHE_FILE = '/composer_consistency_%s.cache';

    /** @var string */
    private $tempDir;

    public function __construct(string $tempDir)
    {
        $this->tempDir = $tempDir;

        if (is_dir($tempDir) === false) {
            /** @noinspection MkdirRaceConditionInspection */
            @mkdir($tempDir, 0777, true);
        }
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function write($key, $value): void
    {
        @file_put_contents($this->getCacheFile($key), serialize($value));
    }

    /**
     * @param mixed $key
     */
    public function invalidate($key): void
    {
        @unlink($this->getCacheFile($key));
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return bool
     */
    public function isValid($key, $value): bool
    {
        $filename = $this->getCacheFile($key);
        return is_file($filename) && file_get_contents($filename) === serialize($value);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function isExists($key): bool
    {
        $filename = $this->getCacheFile($key);
        return is_file($filename);
    }

    /**
     * @param mixed $key
     * @return string
     */
    private function getCacheFile($key): string
    {
        return sprintf($this->tempDir . self::CACHE_FILE, crc32(serialize($key)));
    }
}
