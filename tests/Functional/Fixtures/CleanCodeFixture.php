<?php

declare(strict_types=1);

namespace SimonSchaufi\Scanner\Tests\Functional\Fixtures;

/**
 * Fixture file containing no deprecated TYPO3 API usages.
 * Used by the functional ScannerTest to verify files with no issues produce no matches.
 */
class CleanCodeFixture
{
    public function doSomethingModern(): string
    {
        return implode(', ', ['foo', 'bar', 'baz']);
    }

    public function anotherCleanMethod(int $value): int
    {
        return $value * 2;
    }
}
