<?php

declare(strict_types=1);

namespace JakubBoucek\ComposerConsistency;

class Builder
{
    /** @var string */
    private $rootDir;

    /** @var string|null */
    private $vendorDir;

    /** @var string|null */
    private $tempDir;

    /** @var bool */
    private $strictMode = false;

    /** @var bool */
    private $frozeMode = false;

    /**
     * Behavior on consitency failed
     *
     * - `true`: Throw Exception,
     * - `false`: Do nothing,
     * - `int`: Severity mode: `E_USER_ERROR` | `E_USER_WARNING` | `E_USER_NOTICE` | `E_USER_DEPRECATED`
     *
     * @var bool|int
     */
    private $errorMode = true;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function vendorDir(string $vendorDir): self
    {
        $this->vendorDir = $vendorDir;
        return $this;
    }

    public function cache(string $tempDir): self
    {
        $this->tempDir = $tempDir;
        return $this;
    }

    public function froze(string $tempDir, bool $frozeMode = true): self
    {
        $this->tempDir = $tempDir;
        $this->frozeMode = $frozeMode;
        return $this;
    }


    public function strict(bool $strictMode = true): self
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    public function lax(bool $laxMode = true): self
    {
        $this->strictMode = !$laxMode;
        return $this;
    }

    public function exceptionMode(): self
    {
        $this->errorMode = true;
        return $this;
    }

    /**
     * Behavior on consitency failed
     *
     * - `true`: Throw Exception,
     * - `false`: Do nothing,
     * - `int`: Severity mode: `E_USER_ERROR` | `E_USER_WARNING` | `E_USER_NOTICE` | `E_USER_DEPRECATED`
     *
     * @param int $errorMode
     * @return $this
     */
    public function errorMode(int $errorMode = E_USER_ERROR): self
    {
        $this->errorMode = $errorMode;
        return $this;
    }

    public function silentMode(): self
    {
        $this->errorMode = false;
        return $this;
    }

    public function checker(): ComposerConsistency
    {
        return new ComposerConsistency(
            $this->rootDir,
            $this->vendorDir,
            $this->tempDir,
            $this->strictMode,
            $this->frozeMode,
            $this->errorMode
        );
    }

    public function validate(): void
    {
        $this->checker()->validate();
    }
}
