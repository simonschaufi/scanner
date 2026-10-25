<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/examples',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        docblocks: true,
        spaces: true,
        namespaces: true,
    )
    ->withConfiguredRule(ClassAttributesSeparationFixer::class, [
        'elements' => [
            'property' => 'none',
            'const' => 'none',
            'method' => 'one',
        ],
    ])
    ->withConfiguredRule(CastSpacesFixer::class, [
        'space' => 'none',
    ])
    ->withSkip([
        // Do not format files that are copied from the TYPO3 Core
        __DIR__ . '/src/Php/Matcher',
        __DIR__ . '/src/Utility/GeneralUtility.php',
        __DIR__ . '/tests/Unit/Php/Matcher',
        NotOperatorWithSuccessorSpaceFixer::class,
    ]);
