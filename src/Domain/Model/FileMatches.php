<?php

declare(strict_types=1);

namespace M12R\Scanner\Domain\Model;

/**
 * @extends \ArrayObject<int, ScanMatch>
 */
class FileMatches extends \ArrayObject
{
    public function __construct(
        private readonly string $path,
        ScanMatch ...$matches
    ) {
        parent::__construct(array_values($matches));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
