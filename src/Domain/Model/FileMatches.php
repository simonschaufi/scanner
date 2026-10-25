<?php

declare(strict_types=1);

namespace M12R\Scanner\Domain\Model;

/**
 * @extends \ArrayObject<int, TheMatch>
 */
class FileMatches extends \ArrayObject
{
    public function __construct(
        private readonly string $path,
        TheMatch ...$matches
    ) {
        parent::__construct(array_values($matches));
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
