<?php

namespace M12R\Scanner\Visitor;

use M12R\Scanner\CodeScannerInterface;
use M12R\Scanner\Domain\Model\MatcherBundleCollection;
use M12R\Scanner\Matcher\MatcherFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;

readonly class TraverserFactory
{
    public function __construct(
        private MatcherFactory $matcherFactory
    ) {
    }

    /**
     * @param CodeScannerInterface[]|NodeVisitor[] $matchers
     */
    public function createTraverser(...$matchers): NodeTraverser
    {
        $traverser = $this->buildNodeTraverser();
        // add all matchers to traverser
        foreach ($matchers as $matcher) {
            /** @var NodeVisitor $matcher */
            $traverser->addVisitor($matcher);
        }
        return $traverser;
    }

    /**
     * @return list<CodeScannerInterface&NodeVisitor>
     */
    public function createMatchers(MatcherBundleCollection $collection): array
    {
        return $this->matcherFactory->createAll(
            $collection->getConfiguration()
        );
    }

    private function buildNodeTraverser(): NodeTraverser
    {
        $traverser = new NodeTraverser();
        // The built in NameResolver translates class names shortened with
        // 'use' to fully qualified class names at all places. Incredibly
        // useful for us and added as first visitor.
        $traverser->addVisitor(new NameResolver());
        // Understand makeInstance('My\\Package\\Foo\\Bar') as full-qualified
        // class name in first argument
        $traverser->addVisitor(new GeneratorClassesResolver());
        // count ignored lines, effective code lines, ...
        $traverser->addVisitor(new CodeStatistics());

        return $traverser;
    }
}
