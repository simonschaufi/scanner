<?php

declare(strict_types=1);

namespace M12R\Scanner\Tests\Unit\Matcher;

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

use M12R\Scanner\Matcher\AbstractCoreMatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test case
 */
class AbstractCoreMatcherTest extends TestCase
{
    /**
     * @skip
     */
    #[Test]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function validateMatcherDefinitionsRunsFineWithProperDefinition(): never
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'requiredArg1' => 42,
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $matcher->_call('validateMatcherDefinitions', ['requiredArg1']);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsIfRequiredArgIsNotInConfig(): never
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'someNotRequiredConfig' => '',
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500492001);
        $matcher->_call('validateMatcherDefinitions', ['requiredArg1']);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsWithMissingRestFiles(): never
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500496068);
        $matcher->_call('validateMatcherDefinitions', []);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsWithEmptySingleRestFile(): never
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [
                    'foo.rst',
                    '',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500735983);
        $matcher->_call('validateMatcherDefinitions', []);
    }

    #[Test]
    public function initializeMethodNameArrayThrowsWithInvalidKeys(): never
    {
        $this->markTestSkipped('must be revisited.');

        $matcher = $this->getMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'no\method\given' => [
                'restFiles' => [],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1500557309);
        $matcher->_call('initializeFlatMatcherDefinitions');
    }
}
