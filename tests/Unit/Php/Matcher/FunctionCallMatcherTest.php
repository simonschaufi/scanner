<?php

declare(strict_types=1);

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

namespace M12R\Scanner\Tests\Unit\Php\Matcher;

use M12R\Scanner\Php\Matcher\FunctionCallMatcher;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FunctionCallMatcherTest extends TestCase
{
    #[Test]
    public function hitsFromFixtureAreFound(): void
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 3));
        $fixtureFile = __DIR__ . '/Fixtures/FunctionCallMatcherFixture.php';
        $statements = $parser->parse(file_get_contents($fixtureFile));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $configuration = [
            'debugBegin' => [
                'numberOfMandatoryArguments' => 0,
                'maximumNumberOfArguments' => 0,
                'restFiles' => [
                    'Breaking-37180-RemovedExtDirectDebugAndGLOBALSerror.rst',
                ],
            ],
        ];
        $subject = new FunctionCallMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);
        $expectedHitLineNumbers = [
            28,
            43,
        ];
        $actualHitLineNumbers = [];
        foreach ($subject->getMatches() as $hit) {
            $actualHitLineNumbers[] = $hit['line'];
        }
        self::assertEquals($expectedHitLineNumbers, $actualHitLineNumbers);
    }

    #[Test]
    public function matchIsIgnoredIfIgnoreFileIsSet(): void
    {
        $phpCode = <<<'EOC'
<?php
/**
 * Some comment
 * @extensionScannerIgnoreFile This file is ignored
 */
class foo
{
    public function aTest()
    {
        // This valid match should not match since the entire file is ignored
        debugBegin();
    }
}
EOC;

        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 3));
        $statements = $parser->parse($phpCode);

        $traverser = new NodeTraverser();
        $configuration = [
            'debugBegin' => [
                'numberOfMandatoryArguments' => 0,
                'maximumNumberOfArguments' => 0,
                'restFiles' => [
                    'Breaking-37180-RemovedExtDirectDebugAndGLOBALSerror.rst',
                ],
            ],
        ];
        $subject = new FunctionCallMatcher($configuration);
        $traverser->addVisitor($subject);
        $traverser->traverse($statements);

        self::assertEmpty($subject->getMatches());
    }
}
