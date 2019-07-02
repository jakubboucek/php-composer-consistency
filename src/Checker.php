<?php
declare(strict_types=1);

namespace JakubBoucek\ComposerVendorChecker;

use RuntimeException;

/**
 * Class Checker
 * Comparing packages promised by `composer.lock` in versioned part of your project and actually installed packages
 * in `/vendor` directory.
 * @package JakubBoucek\ComposerVendorChecker
 */
class Checker
{
    /**
     * @var string
     */
    private $rootDir;
    /**
     * @var string
     */
    private $vendorDir;


    /**
     * Create checker instance
     * @param string $rootDir Absolute path to your project root with `composer.lock` file
     * @param string|null $vendorDir Absolute path to your `/vendor` path. Optional.
     */
    public function __construct(string $rootDir, ?string $vendorDir = null)
    {
        $vendorDir = $vendorDir ?? $rootDir . '/vendor';
        $this->rootDir = $rootDir;
        $this->vendorDir = $vendorDir;
    }


    /**
     * Check validity of required packages in vendor - shortcut
     * If `/vendor` is not comply requirements, checker throws an Exception
     * @param string $rootDir
     * @param string|null $vendorDir
     * @throws RuntimeException
     */
    public static function validateReqs(string $rootDir, ?string $vendorDir = null): void
    {
        (new self($rootDir, $vendorDir))->validate();
    }


    /**
     * Check validity of required packages in vendor
     * If `/vendor` is not comply requirements, checker throws an Exception
     * @throws RuntimeException
     */
    public function validate(): void
    {
        if ($this->isReqsValid() === false) {
            throw new RuntimeException(
                'Composer has no consitent vendor directory with project requirements, call `composer install`'
            );
        }
    }


    /**
     * If vendor is not comply requirements, checker return `false`
     * @return bool
     * @throws RuntimeException
     */
    public function isReqsValid(): bool
    {
        $definitions = $this->loadReqs();
        $instalations = $this->loadInstalled();

        $diff = array_diff_assoc($definitions, $instalations);

        return count($diff) === 0;
    }


    /**
     * @return array
     * @throws RuntimeException
     */
    protected function loadReqs(): array
    {
        $data = $this->loadReqsFile();

        $reqs = [];

        foreach ($data['packages'] as $package) {
            $reqs[$package['name']] = $package['version'];
        }

        return $reqs;
    }


    /**
     * @return array
     * @throws RuntimeException
     */
    protected function loadReqsFile(): array
    {
        return $this->readJsonFile($this->rootDir . '/composer.lock');
    }


    /**
     * @return array
     * @throws RuntimeException
     */
    protected function loadInstalled(): array
    {
        $data = $this->loadInstalledFile();

        $installed = [];

        foreach ($data as $package) {
            $installed[$package['name']] = $package['version'];
        }

        return $installed;
    }


    /**
     * @return array
     * @throws RuntimeException
     */
    protected function loadInstalledFile(): array
    {
        return $this->readJsonFile($this->vendorDir . '/composer/installed.json');
    }


    /**
     * @param string $filename
     * @return array
     * @throws RuntimeException
     */
    protected function readJsonFile(string $filename): array
    {
        $file = $this->readFile($filename);
        return $this->parseJsonArray($file);
    }


    /**
     * @param string $file
     * @return string
     * @throws RuntimeException
     * @link https://doc.nette.org/en/3.0/filesystem
     */
    protected function readFile(string $file): string
    {
        $content = @file_get_contents($file); // @ is escalated to exception
        if ($content === false) {
            $lastError = preg_replace('#^\w+\(.*?\): #', '', error_get_last()['message']);
            throw new RuntimeException("Unable to read file '$file'. " . $lastError);
        }
        return $content;
    }


    /**
     * @param string $json
     * @return array
     * @throws RuntimeException
     * @link https://doc.nette.org/en/3.0/json
     */
    protected function parseJsonArray(string $json): array
    {
        $value = json_decode($json, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
        if (is_array($value) === false) {
            $type = gettype($value);
            throw new RuntimeException("Expected Json-serialized Array, but $type instead.");
        }
        return $value;
    }
}
