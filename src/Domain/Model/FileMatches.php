<?php

namespace M12R\Scanner\Domain\Model;

class FileMatches extends \ArrayObject
{
    private string $path;

    public function __construct(string $path, TheMatch ...$matches)
    {
        parent::__construct($matches);
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
