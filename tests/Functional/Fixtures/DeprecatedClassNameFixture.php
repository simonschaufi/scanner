<?php

declare(strict_types=1);

namespace SimonSchaufi\Scanner\Tests\Functional\Fixtures;

use TYPO3\CMS\Backend\Console\Application;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;

/**
 * Fixture file containing deprecated/removed TYPO3 class name usages.
 * Used by the functional ScannerTest to verify ClassNameMatcher produces matches.
 */
class DeprecatedClassNameFixture
{
    public function useDeprecatedClasses(): void
    {
        // Match: TYPO3\CMS\Backend\Console\Application was removed
        $app = new Application();

        // Match: DriverRegistry->addDriversToTCA() was removed
        $registry = new DriverRegistry();
        $registry->addDriversToTCA();
    }
}
