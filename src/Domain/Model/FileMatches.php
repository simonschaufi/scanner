<?php

declare(strict_types=1);

namespace M12R\Scanner\Domain\Model;

class FileMatches extends \ArrayObject
{
    public function __construct(
        private readonly string $path,
        TheMatch ...$matches
    ) {
        parent::__construct($matches);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
