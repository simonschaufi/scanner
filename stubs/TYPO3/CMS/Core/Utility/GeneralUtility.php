<?php

namespace TYPO3\CMS\Core\Utility;

if (class_exists('TYPO3\CMS\Core\Utility\GeneralUtility')) {
    return;
}

class GeneralUtility
{
    /**
     * @template T of object
     * @phpstan-param class-string<T> $className
     * @phpstan-param mixed $constructorArguments
     * @phpstan-return T
     */
    public static function makeInstance($className, ...$constructorArguments)
    {
    }
}
