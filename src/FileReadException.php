<?php
declare(strict_types=1);

namespace JakubBoucek\ComposerConsistency;

use RuntimeException;
use Throwable;

class FileReadException extends RuntimeException
{
    /**
     * @var string
     */
    private $requiredfile;

    public function __construct(string $requiredfile, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->requiredfile = $requiredfile;
    }

    /**
     * @return string
     */
    public function getRequiredfile(): string
    {
        return $this->requiredfile;
    }
}
