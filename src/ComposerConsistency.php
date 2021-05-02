<?php

declare(strict_types=1);

namespace JakubBoucek\ComposerConsistency;

use JakubBoucek\ComposerConsistency\Cache\FileCache;
use JakubBoucek\ComposerConsistency\Exception\ComposerInconsitencyException;
use JakubBoucek\ComposerConsistency\Exception\FileReadException;
use JsonException;
use LogicException;

/**
 * Class Checker
 * Comparing packages promised by `composer.lock` in versioned part of your project and actually installed packages
 * in `/vendor` directory.
 */
class ComposerConsistency
{
    private const FILE_REQS = 'composer.lock';
    private const FILE_INSTALLED = 'installed.json';

    private const ALLOWED_ERROR_SEVERITY = [E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED];

    /** @var string */
    private $rootDir;

    /** @var string */
    private $vendorDir;

    /** @var FileCache|null */
    private $cache;

    /** @var bool Is strictly required `composer.lock` files? */
    private $strictMode;

    /** @var bool */
    private $frozeMode;

    /** @var bool|int */
    private $errorMode;


    /**
     * Create checker instance
     * @param string $rootDir Absolute path to your project root with `composer.lock` file
     * @param string|null $vendorDir Absolute path to your `/vendor` path. Optional.
     * @param string|null $tempDir
     * @param bool $strictMode
     * @param bool $frozeMode
     * @param bool|int $errorMode
     */
    public function __construct(
        string $rootDir,
        ?string $vendorDir = null,
        ?string $tempDir = null,
        bool $strictMode = false,
        bool $frozeMode = false,
        $errorMode = true
    ) {
        $vendorDir = $vendorDir ?? $rootDir . '/vendor';

        $this->rootDir = $rootDir;
        $this->vendorDir = $vendorDir;
        $this->cache = $tempDir ? new FileCache($tempDir) : null;
        $this->strictMode = $strictMode;
        $this->frozeMode = $frozeMode;
        $this->errorMode = $errorMode;
    }

    public static function rootDir(string $rootDir): Builder
    {
        return new Builder($rootDir);
    }

    public function validate(): void
    {
        // Silent mode - do nothing
        if ($this->errorMode === false) {
            return;
        }

        // Frozen mode - only look if result cache exists for Composer  files
        if ($this->frozeMode && $this->cache && $this->cache->isExists($this->getFileNames())) {
            return;
        }

        // Cache mode - compare file hashes
        if ($this->cache && $this->cache->isValid($this->getFileNames(), $this->getFileHashes())) {
            return;
        }

        if ($this->isValid()) {
            // Save file hashes
            if ($this->cache) {
                $this->cache->write($this->getFileNames(), $this->getFileHashes());
            }

            return;
        }

        // Cache mode - invalidate cache
        if ($this->cache) {
            $this->cache->invalidate($this->getFileNames());
        }

        $error = "Composer has no consistent 'vendor/' directory with project requirements, call `composer install`";
        if (in_array($this->errorMode, self::ALLOWED_ERROR_SEVERITY, true)) {
            trigger_error($error, $this->errorMode);
        } else {
            throw new ComposerInconsitencyException($error);
        }
    }

    public function isValid(): bool
    {
        $results = $this->check();
        return count($results['prod']) === 0 || count($results['dev']) === 0;
    }

    /**
     * @return array<string, array>
     */
    public function check(): array
    {
        try {
            ['prod' => $definitions, 'dev' => $devDefinitions] = $this->loadReqs();
            $instalations = $this->loadInstalled();

            return [
                'prod' => $this->compareReqs($definitions, $instalations),
                'dev' => $this->compareReqs($devDefinitions, $instalations)
            ];
        } catch (FileReadException $e) {
            if ($this->strictMode !== true && $e->getRequiredfile() === self::FILE_REQS) {
                // Ignore missing `composer.lock` file - not deployed to production?
                return ['prod' => [], 'dev' => []];
            }
            throw $e;
        }
    }

    /**
     * @return array<string, array>
     */
    private function loadReqs(): array
    {
        $data = $this->readJsonFile($this->getReqsFilename());

        $packages = [];

        foreach ($data['packages'] as $package) {
            $packages[$package['name']] = $package['version'];
        }

        $packagesDev = $packages;

        foreach ($data['packages-dev'] as $package) {
            $packagesDev[$package['name']] = $package['version'];
        }
        return ['prod' => $packages, 'dev' => $packagesDev];
    }


    private function getReqsFilename(): string
    {
        return $this->rootDir . '/' . self::FILE_REQS;
    }

    /**
     * @return array<string, string>
     */
    private function loadInstalled(): array
    {
        $data = $this->loadInstalledFile();

        $installed = [];

        // Composer v1 vs v2
        $packages = $data['packages'] ?? $data;

        foreach ($packages as $package) {
            $installed[$package['name']] = $package['version'];
        }

        return $installed;
    }

    /**
     * @return array<array>
     */
    private function loadInstalledFile(): array
    {
        return $this->readJsonFile($this->getInstalledFilename());
    }

    private function getInstalledFilename(): string
    {
        return $this->vendorDir . '/composer/' . self::FILE_INSTALLED;
    }

    /**
     * @param string $filename
     * @return array<array>
     */
    private function readJsonFile(string $filename): array
    {
        $file = $this->readFile($filename);
        return $this->parseJsonArray($file);
    }

    private function readFile(string $file): string
    {
        $content = @file_get_contents($file); // @ is escalated to exception
        if ($content === false) {
            $lastError = preg_replace('#^\w+\(.*?\): #', '', (string)error_get_last()['message']);
            $requiredFile = basename($file);
            throw new FileReadException($requiredFile, "Unable to read file '$file'. " . $lastError);
        }
        return $content;
    }

    /**
     * @param string $json
     * @return array<array>
     */
    private function parseJsonArray(string $json): array
    {
        try {
            $value = json_decode($json, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
            if (is_array($value) === false) {
                $type = gettype($value);
                throw new LogicException("Expected Json-serialized Array, but $type instead.");
            }
            return $value;
        } catch (JsonException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string> $required
     * @param array<string, string> $installed
     * @return array<string, array>
     */
    private function compareReqs(array $required, array $installed): array
    {
        $diffs = array_map(
            static function ($version) {
                return ['required' => $version, 'installed' => null];
            },
            $required
        );

        foreach ($installed as $name => $version) {
            if (($diffs[$name]['required'] ?? null) === $version) {
                // Version matches, remove diff
                unset($diffs[$name]);
            } else {
                $diffs[$name] = [
                    'required' => $diffs[$name]['required'] ?? null,
                    'installed' => $version
                ];
            }
        }

        return $diffs;
    }

    /**
     * @return array<int, string>
     */
    private function getFileNames(): array
    {
        return [$this->getReqsFilename(), $this->getInstalledFilename()];
    }

    /**
     * @return array<string, string>
     */
    private function getFileHashes(): array
    {
        $files = [$this->getReqsFilename() => null, $this->getInstalledFilename() => null];
        foreach ($files as $filename => $null) {
            $files[$filename] = md5($this->readFile($filename));
        }
        return $files;
    }
}
