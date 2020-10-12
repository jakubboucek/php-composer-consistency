<?php

declare(strict_types=1);

namespace JakubBoucek\ComposerConsistency\Exception;

use LogicException;
use Throwable;

class FileReadException extends LogicException
{
    /**
     * @var string
     */
    private $requiredfile;

    public function __construct(string $requiredfile, string $message = '', int $code = 0, ?Throwable $previous = null)
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
