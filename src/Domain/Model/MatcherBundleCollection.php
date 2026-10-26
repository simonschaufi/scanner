<?php

namespace SimonSchaufi\Scanner\Domain\Model;

/**
 * @extends \ArrayObject<int, MatcherBundle>
 */
class MatcherBundleCollection extends \ArrayObject
{
    public function __construct(MatcherBundle ...$bundles)
    {
        parent::__construct(array_values($bundles));
    }

    /**
     * @return array<int, array{class: string, configurationFile: string}>
     */
    public function getConfiguration(): array
    {
        $matcherBundleConfiguration = array_map(
            static fn (MatcherBundle $bundle) => $bundle->getConfiguration(),
            $this->getArrayCopy()
        );

        return array_merge(...$matcherBundleConfiguration);
    }
}
