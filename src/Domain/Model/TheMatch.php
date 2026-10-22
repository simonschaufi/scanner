<?php

namespace M12R\Scanner\Domain\Model;

class TheMatch
{
    public const string TYPE_BREAKING = 'BREAKING';
    public const string TYPE_DEPRECATION = 'DEPRECATION';
    public const string TYPE_IMPORTANT = 'IMPORTANT';
    public const string TYPE_FEATURE = 'FEATURE';

    private string $matcher;
    private string|array $identifier;
    private string $indicator;
    private string $subject;
    private string $message;
    private int $line;
    private string $type;
    private array $restFiles = [];

    public function __construct(
        string $matcher,
        string $indicator,
        string $subject,
        string $message,
        int $line,
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

        $this->matcher = $matcher;
        $this->indicator = $indicator;
        $this->subject = $subject;
        $this->message = $message;
        $this->line = $line;
        $this->type = $this->validateType($type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    public function getMatcher(): string
    {
        return $this->matcher;
    }

    public function getIndicator(): string
    {
        return $this->indicator;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getRestFiles(): array
    {
        return $this->restFiles;
    }

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
