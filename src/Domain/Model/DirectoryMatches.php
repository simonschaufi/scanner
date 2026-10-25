<?php

namespace M12R\Scanner\Domain\Model;

use M12R\Scanner\Utility;

class DirectoryMatches extends \ArrayObject
{
    private readonly string $path;

    public function __construct(string $path, FileMatches ...$fileMatches)
    {
        parent::__construct($fileMatches);
        $this->path = Utility::ensureTrailingDirectorySeparator($path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function countAll(): int
    {
        $counts = array_map(
            static fn (FileMatches $fileMatches) => $fileMatches->count(),
            $this->getArrayCopy()
        );
        return array_sum($counts);
    }
}
