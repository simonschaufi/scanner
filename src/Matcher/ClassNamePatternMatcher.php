<?php

declare(strict_types=1);

namespace M12R\Scanner\Matcher;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;

/**
 * This Matcher is not part of TYPO3 Core
 *
 * Find usages of class / interface names which are entirely deprecated or removed
 */
class ClassNamePatternMatcher extends AbstractCoreMatcher
{
    /**
     * Default constructor validates matcher definition.
     *
     * @param array<string, array<string, mixed>> $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
    }

    public function enterNode(Node $node): null
    {
        if (!$node instanceof FullyQualified
            || $this->isFileIgnored($node)
            || $this->isLineIgnored($node)
        ) {
            return null;
        }

        $fullyQualifiedClassName = $node->toString();
        $matchedPattern = $this->match($fullyQualifiedClassName);
        if ($matchedPattern === null) {
            return null;
        }

        $this->matches[] = [
            'restFiles' => $this->matcherDefinitions[$matchedPattern]['restFiles'],
            'line' => $node->getAttribute('startLine'),
            'subject' => $fullyQualifiedClassName,
            'message' => 'Usage of class "' . $fullyQualifiedClassName . '"',
            'indicator' => 'strong',
        ];
        return null;
    }

    private function match(string $value): ?string
    {
        foreach (array_keys($this->matcherDefinitions) as $pattern) {
            if (preg_match($pattern, $value)) {
                return $pattern;
            }
        }

        return null;
    }
}
