<?php

declare(strict_types=1);

use SimonSchaufi\Scanner\Domain\Model\MatcherBundle;
use SimonSchaufi\Scanner\Domain\Model\MatcherBundleCollection;
use SimonSchaufi\Scanner\Php\Matcher;
use SimonSchaufi\Scanner\ScannerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL ^ E_NOTICE);

$collection = new MatcherBundleCollection(
    new MatcherBundle(
        __DIR__ . '/../config/Matcher/v14',
        [
            Matcher\ArrayDimensionMatcher::class,
            Matcher\ArrayGlobalMatcher::class,
            Matcher\ClassConstantMatcher::class,
            Matcher\ClassNameMatcher::class,
            Matcher\ConstantMatcher::class,
            Matcher\ConstructorArgumentMatcher::class,
            Matcher\PropertyAnnotationMatcher::class,
            Matcher\MethodAnnotationMatcher::class,
            Matcher\FunctionCallMatcher::class,
            Matcher\AbstractMethodImplementationMatcher::class,
            Matcher\InterfaceMethodChangedMatcher::class,
            Matcher\MethodArgumentDroppedMatcher::class,
            Matcher\MethodArgumentDroppedStaticMatcher::class,
            Matcher\MethodArgumentRequiredMatcher::class,
            Matcher\MethodArgumentRequiredStaticMatcher::class,
            Matcher\MethodArgumentUnusedMatcher::class,
            Matcher\MethodCallMatcher::class,
            Matcher\MethodCallArgumentValueMatcher::class,
            Matcher\MethodCallStaticMatcher::class,
            Matcher\PropertyExistsStaticMatcher::class,
            Matcher\PropertyProtectedMatcher::class,
            Matcher\PropertyPublicMatcher::class,
            Matcher\ScalarStringMatcher::class,
        ]
    )
);

$scanner = ScannerFactory::create()->createFor(8, 3);

// clone news extension into examples directory to test it
// `git clone https://github.com/georgringer/news.git`
$result = $scanner->scanPath(
    __DIR__ . '/news/',
    $collection
);

var_dump($result);
