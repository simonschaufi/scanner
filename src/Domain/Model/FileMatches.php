<?php

declare(strict_types=1);

namespace M12R\Scanner\Domain\Model;

/**
 * @extends \ArrayObject<int, ScanMatch>
 */
class FileMatches extends \ArrayObject
{
    /**
     * @param ScanMatch[] $matches
     */
    public function __construct(
        private readonly string $path,
        array $matches
    ) {
        parent::__construct(array_values($matches));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
