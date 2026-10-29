<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassLike\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/examples',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withSets([
        SetList::CODE_QUALITY,
        PHPUnitSetList::COMPOSER_BASED,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        FinalizeTestCaseClassRector::class,
    ])
    ->withSkip([
        __DIR__ . '/src/Php/Matcher',
        __DIR__ . '/src/Utility/GeneralUtility.php',
        __DIR__ . '/tests/Unit/Php/Fixtures',
        __DIR__ . '/tests/Unit/Php/Matcher/Fixtures',
        ExplicitNullableObjectInstanceRector::class,
    ]);
