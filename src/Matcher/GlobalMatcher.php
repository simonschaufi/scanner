<?php

declare(strict_types=1);

namespace M12R\Scanner\Matcher;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
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
     * @param array<string, array<string, mixed>> $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
    }

    public function enterNode(Node $node): null
    {
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof MethodCall
            && $node->var instanceof Variable
            && is_string($node->var->name)
            && $node->var->name !== 'GLOBALS'
            && array_key_exists('$' . $node->var->name, $this->matcherDefinitions)
        ) {
            $this->matches[] = [
                'restFiles' => $this->matcherDefinitions['$' . $node->var->name]['restFiles'],
                'line' => $node->getStartLine(),
                'message' => 'Usage of global "' . $node->var->name . '"',
                'indicator' => 'strong',
            ];
        }
        return null;
    }
}
