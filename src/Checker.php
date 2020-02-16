<?php
declare(strict_types=1);

namespace JakubBoucek\ComposerConsistency;

use RuntimeException;

/**
 * Class Checker
 * Comparing packages promised by `composer.lock` in versioned part of your project and actually installed packages
 * in `/vendor` directory.
 */
class Checker
{
    protected const FILE_REQS = 'composer.lock';
    protected const FILE_INSTALLED = 'installed.json';

    /** @var string */
    private $rootDir;

    /** @var string */
    private $vendorDir;

    /** @var bool Is strictly required `composer.lock` files? */
    private $strictReqs = true;


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
        try {
            $definitions = $this->loadReqs();
            $instalations = $this->loadInstalled();

            $diff = array_diff_assoc($definitions, $instalations);

            return count($diff) === 0;
        } catch (FileReadException $e) {
            if($this->strictReqs !== true && $e->getRequiredfile() === self::FILE_REQS) {
                // Ignore missing `composer.lock` file - not deployed to production?
                return true;
            }
            throw $e;
        }
    }


    /**
     * @return bool
     */
    public function isStrictReqs(): bool
    {
        return $this->strictReqs;
    }

    /**
     * @param bool $strictReqs
     * @return Checker
     */
    public function setStrictReqs(bool $strictReqs): self
    {
        $this->strictReqs = $strictReqs;
        return $this;
    }


    /**
     * @return array
     * @throws FileReadException
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
     * @throws FileReadException
     * @throws RuntimeException
     */
    protected function loadReqsFile(): array
    {
        return $this->readJsonFile($this->rootDir . '/' . self::FILE_REQS);
    }


    /**
     * @return array
     * @throws FileReadException
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
     * @throws FileReadException
     * @throws RuntimeException
     */
    protected function loadInstalledFile(): array
    {
        return $this->readJsonFile($this->vendorDir . '/composer/' . self::FILE_INSTALLED);
    }


    /**
     * @param string $filename
     * @return array
     * @throws FileReadException
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
     * @throws FileReadException
     * @link https://doc.nette.org/en/3.0/filesystem
     */
    protected function readFile(string $file): string
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
