<?php

declare(strict_types=1);

namespace M12R\Scanner\Matcher;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;

/**
 * This Matcher is not part of TYPO3 Core
 *
 * Match access to a one dimensional '$GLOBAL' array
 * Example "$TT"
 */
class GlobalMatcher extends AbstractCoreMatcher
{
    /**
     * Initialize "flat" matcher array from matcher definitions.
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
    }

    public function enterNode(Node $node): void
    {
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof Node\Expr\MethodCall
            && $node->var instanceof Variable
            && $node->var->name !== 'GLOBALS'
            && array_key_exists('$' . $node->var->name, $this->matcherDefinitions)
        ) {
            $this->matches[] = [
                'restFiles' => $this->matcherDefinitions['$' . $node->var->name]['restFiles'],
                'line' => $node->getAttribute('startLine'),
                'subject' => $node->var->name,
                'message' => 'Usage of global "' . $node->var->name . '"',
                'indicator' => 'strong',
            ];
        }
    }
}
