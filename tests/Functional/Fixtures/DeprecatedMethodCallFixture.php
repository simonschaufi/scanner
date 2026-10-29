<?php

declare(strict_types=1);

namespace SimonSchaufi\Scanner\Tests\Functional\Fixtures;

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Fixture file containing various deprecated/removed TYPO3 API usages.
 * Used by the functional ScannerTest to verify the Scanner produces matches.
 */
class DeprecatedMethodCallFixture
{
    public function callDeprecatedInstanceMethods(ResourceStorage $storage, Clipboard $clipboard): void
    {
        // Match: ResourceStorage->dumpFileContents() is deprecated in v14
        $storage->dumpFileContents('some/file.txt');

        // Match: Clipboard->confirmMsg() was removed (needs 4-5 args)
        $clipboard->confirmMsg('table', 'rec', 'type', 'messages');
    }

    public function callDeprecatedStaticMethods(): void
    {
        // Match: BackendUtility::getAjaxUrl() was removed
        BackendUtility::getAjaxUrl('some_route');

        // Match: BackendUtility::getRecordRaw() was removed
        BackendUtility::getRecordRaw('pages');
    }
}
