<?php

declare(strict_types=1);

namespace M12R\Scanner\Tests\Unit\Php\Matcher\Fixtures;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Fixture file
 */
class ClassNamePatternMatcherFixture
{
    public function aMethod(): void
    {
        // Match: "t3lib_" pattern
        $foo = new \t3lib_div();
        $bar = \t3lib_div::class;

        // Match: "tslib_" pattern
        $baz = new \tslib_feTce();

        // Match: "tx_" pattern, defined with the case insensitive modifier
        $qux = new \tx_cms_ExtensionManager();

        // Match: string class name turned into a class name by GeneratorClassesResolver
        $resolved = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_htmlmail');

        // No match: the "t3lib_" pattern is case sensitive
        $noMatch = new \T3LIB_DIV();

        // No match: no pattern matches this name
        $noMatch = new \Some\Package\NotMatching();

        // @extensionScannerIgnoreLine
        $ignored = new \t3lib_div();

        // Match (again). No longer ignored.
        $afterIgnore = new \t3lib_div();
    }
}
