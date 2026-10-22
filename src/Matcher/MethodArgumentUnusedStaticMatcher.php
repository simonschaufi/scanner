<?php

declare(strict_types=1);

namespace M12R\Scanner\Matcher;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;

/**
 * This Matcher is not part of TYPO3 Core
 *
 * Match static method usages where arguments "in between" are unused but not given as "null":
 *
 * public function foo($arg1, $unsused1 = null, $unused2 = null, $arg4)
 * but called with:
 * ->foo('arg1', 'notNull', null, 'arg4');
 * This is a "strong" match if class name is given and "weak" if not.
 */
class MethodArgumentUnusedStaticMatcher extends AbstractCoreMatcher
{
    /**
     * Prepare $this->flatMatcherDefinitions once and validate config
     *
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions(['unusedArgumentNumbers']);
        $this->initializeFlatMatcherDefinitions();
    }

    /**
     * Test for "::function($1, $2, $3)" (strong match)
     */
    public function enterNode(Node $node): void
    {
        // Match static call
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof StaticCall
        ) {
            $isArgumentUnpackingUsed = $this->isArgumentUnpackingUsed($node->args);

            if ($node->class instanceof FullyQualified && $node->name instanceof Identifier) {
                // 'Foo\Bar::aMethod()' -> strong match
                $fqdnClassWithMethod = $node->class->toString() . '::' . $node->name->name;
                $numberOfArguments = count($node->args);

                if (in_array($fqdnClassWithMethod, array_keys($this->matcherDefinitions), true)) {
                    foreach ($this->matcherDefinitions[$fqdnClassWithMethod]['unusedArgumentNumbers'] as $droppedArgumentNumber) {
                        if (!$isArgumentUnpackingUsed
                            && $numberOfArguments >= $droppedArgumentNumber
                            && !($node->args[$droppedArgumentNumber - 1]->value instanceof ConstFetch)
                            && (!isset($node->args[$droppedArgumentNumber - 1]->value->name->name->parts[0])
                                || $node->args[$droppedArgumentNumber - 1]->value->name->name->parts[0] !== null)
                        ) {
                            $this->matches[] = [
                                'restFiles' => $this->matcherDefinitions[$fqdnClassWithMethod]['restFiles'],
                                'line' => $node->getAttribute('startLine'),
                                'subject' => $node->name->name,
                                'message' => 'Call to method "' . $node->name->name . '()" with'
                                    . ' argument ' . $droppedArgumentNumber . ' not given as null.',
                                'indicator' => 'strong',
                            ];
                        }
                    }
                }
            } elseif ($node->class instanceof Variable
                && $node->name instanceof Identifier
                && in_array($node->name->name, array_keys($this->flatMatcherDefinitions), true)
            ) {
                $match = [
                    'restFiles' => [],
                    'line' => $node->getAttribute('startLine'),
                    'indicator' => 'weak',
                ];

                $numberOfArguments = count($node->args);
                $isPossibleMatch = false;
                foreach ($this->flatMatcherDefinitions[$node->name->name]['candidates'] as $candidate) {
                    foreach ($candidate['unusedArgumentNumbers'] as $droppedArgumentNumber) {
                        // A method call is considered a match if name matches, unpacking is not used
                        // and the registered argument is not given as null.
                        if (!$isArgumentUnpackingUsed
                            && $numberOfArguments >= $droppedArgumentNumber
                            && !($node->args[$droppedArgumentNumber - 1]->value instanceof ConstFetch)
                            && (!isset($node->args[$droppedArgumentNumber - 1]->value->name->name->parts[0])
                                || $node->args[$droppedArgumentNumber - 1]->value->name->name->parts[0] !== null)
                        ) {
                            $isPossibleMatch = true;
                            $match['subject'] = $node->name->name;
                            $match['message'] = 'Call to method "' . $node->name->name . '()" with'
                                . ' argument ' . $droppedArgumentNumber . ' not given as null.';
                            $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
                        }
                    }
                }
                if ($isPossibleMatch) {
                    $this->matches[] = $match;
                }
            }
        }
    }
}
