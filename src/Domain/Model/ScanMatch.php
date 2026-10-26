<?php

declare(strict_types=1);

namespace M12R\Scanner\Domain\Model;

use M12R\Scanner\Result\Indicator;

class ScanMatch
{
    public const string TYPE_BREAKING = 'BREAKING';
    public const string TYPE_DEPRECATION = 'DEPRECATION';
    public const string TYPE_IMPORTANT = 'IMPORTANT';
    public const string TYPE_FEATURE = 'FEATURE';

    private readonly string $identifier;
    private readonly string $type;

    /**
     * @var list<string> Documentation files this match refers to
     */
    private array $restFiles = [];

    public function __construct(
        private readonly string $matcher,
        private readonly Indicator $indicator,
        private readonly string $message,
        private readonly int $line,
        string $type
    ) {
        $this->identifier = str_replace(
            '.',
            '',
            uniqid(
                (string)mt_rand(),
                true
            )
        );
        $this->type = $this->validateType($type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getMatcher(): string
    {
        return $this->matcher;
    }

    public function getIndicator(): Indicator
    {
        return $this->indicator;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return list<string>
     */
    public function getRestFiles(): array
    {
        return $this->restFiles;
    }

    /**
     * @param list<string> $restFiles
     */
    public function setRestFiles(array $restFiles): void
    {
        $this->restFiles = $restFiles;
    }

    private function validateType(string $type): string
    {
        $validTypes = [
            self::TYPE_BREAKING,
            self::TYPE_DEPRECATION,
            self::TYPE_FEATURE,
            self::TYPE_IMPORTANT,
        ];
        if (!in_array($type, $validTypes, true)) {
            throw new \RuntimeException('Invalid type ' . htmlspecialchars($type) . ' given.', 1513148761);
        }
        return $type;
    }
}
