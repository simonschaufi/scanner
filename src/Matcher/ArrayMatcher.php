<?php

declare(strict_types=1);

namespace M12R\Scanner\Matcher;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;

/**
 * This Matcher is not part of TYPO3 Core
 *
 * Find usages of dropped TCA configuration values and other
 * nested array structures.
 *
 * You can specify the `matchOnValues` parameter to pass in an array
 * of values to match.
 *
 * Remember array startline with the match so we can skip
 * traversal of nodes after we have found a match.
 */
class ArrayMatcher extends AbstractCoreMatcher
{
    /**
     * @var list<string> Already matched "start line + candidate key" combinations
     */
    protected array $matchedNodes = [];

    /**
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
            && $node instanceof Array_
        ) {
            $flatArray = $this->flattenArray($node);

            foreach ($this->matcherDefinitions as $candidateKey => $candidate) {
                $candidateKey = '/' . ltrim($candidateKey, '/');
                foreach ($flatArray as $flatKey => $nodeItem) {
                    // flattenArray() only collects items with a string key, re-check for the type narrowing
                    if (!$nodeItem->key instanceof String_) {
                        continue;
                    }
                    if (\strlen($candidateKey) > \strlen($flatKey)) {
                        continue;
                    }
                    if (($candidateKey === $flatKey || str_ends_with($flatKey, $candidateKey))
                        && !\in_array($nodeItem->getStartLine() . $candidateKey, $this->matchedNodes, true)
                    ) {
                        if (array_key_exists('matchOnValues', $candidate) && !$this->nodeValueMatches($nodeItem->value, $candidate['matchOnValues'])) {
                            continue;
                        }
                        $match = [
                            'restFiles' => [],
                            'line' => $nodeItem->getStartLine(),
                            'message' => 'Usage of array key "' . $nodeItem->key->value . '"',
                            'indicator' => substr_count($candidateKey, '/') > 1 ? 'strong' : 'weak',
                        ];
                        $match['restFiles'] = array_unique(array_merge($match['restFiles'], $candidate['restFiles']));
                        $this->matches[] = $match;
                        $this->matchedNodes[] = $nodeItem->getStartLine() . $candidateKey;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Flatten the AST Array_ object into a simple array indexed with the same key
     * used in the matcher.
     *
     * This will result in an array of keys containing the 'end' nodeItem objects.
     *
     * @return array<string, ArrayItem>
     */
    protected function flattenArray(Array_ $node, string $prefix = '/'): array
    {
        $result = [];
        foreach ($node->items as $nodeItem) {
            if ($nodeItem->value instanceof Array_ && $nodeItem->value->items) {
                if ($nodeItem instanceof ArrayItem && $nodeItem->key instanceof String_) {
                    $result += $this->flattenArray($nodeItem->value, $prefix . $nodeItem->key->value . '/');
                }
            } else {
                if ($nodeItem instanceof ArrayItem && $nodeItem->key instanceof String_) {
                    $result[$prefix . $nodeItem->key->value] = $nodeItem;
                }
            }
        }
        return $result;
    }

    /**
     * Try to extract the value from the node and match it to the array of available matches
     *
     * @param object $nodeValue
     * @param list<mixed> $matches
     * @return bool
     */
    protected function nodeValueMatches(object $nodeValue, array $matches = []): bool
    {
        $value = null;
        switch (\get_class($nodeValue)) {
            case Node\Scalar\Int_::class:
                $value = $nodeValue->value;
                break;
            case Node\Expr\ConstFetch::class:
                $parts = $nodeValue->name->getParts();
                if (\count($parts) === 1) {
                    $value = $parts[0];
                }
                break;
            default:
                if (isset($nodeValue->value)) {
                    $value = $nodeValue->value;
                }
        }

        return \in_array($value, $matches, true);
    }
}
