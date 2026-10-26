<?php

declare(strict_types=1);

namespace SimonSchaufi\Scanner;

class Utility
{
    public static function ensureTrailingDirectorySeparator(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public static function removeTrailingDirectorySeparator(string $path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}
