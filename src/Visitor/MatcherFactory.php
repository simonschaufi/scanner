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

namespace M12R\Scanner\Visitor;

use M12R\Scanner\CodeScannerInterface;
use PhpParser\NodeVisitor;

/**
 * Factory preparing matcher instances
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MatcherFactory
{
    /**
     * Create matcher instances and hand over configuration.
     *
     * @param array<int, array{class?: string, restFilePath?: string, configurationFile?: string, configurationArray?: mixed}> $matcherConfigurations Incoming configuration array
     * @return NodeVisitor[]|CodeScannerInterface[]
     * @throws \RuntimeException
     */
    public function createAll(array $matcherConfigurations): array
    {
        $instances = [];
        foreach ($matcherConfigurations as $matcherConfiguration) {
            if (empty($matcherConfiguration['class'])) {
                throw new \RuntimeException(
                    'Each matcher must have a class name',
                    1501415721
                );
            }

            if (empty($matcherConfiguration['configurationFile']) && !isset($matcherConfiguration['configurationArray'])) {
                throw new \RuntimeException(
                    'Each matcher must have either a configurationFile or configurationArray defined',
                    1501416365
                );
            }

            if (isset($matcherConfiguration['configurationFile'], $matcherConfiguration['configurationArray'])) {
                throw new \RuntimeException(
                    'Having both a configurationFile and configurationArray is invalid',
                    1501419367
                );
            }

            $configuration = [];
            if (isset($matcherConfiguration['configurationFile'])) {
                $configuration = $matcherConfiguration['configurationFile'];
                if (empty($configuration) || !is_file($configuration)) {
                    throw new \RuntimeException(
                        'Configuration file ' . $matcherConfiguration['configurationFile'] . ' not found',
                        1501509605
                    );
                }
                $configuration = require $configuration;
                if (!is_array($configuration)) {
                    throw new \RuntimeException(
                        'Configuration file ' . $matcherConfiguration['configurationFile'] . ' must return an array',
                        1501509548
                    );
                }
            }

            if (isset($matcherConfiguration['configurationArray'])) {
                if (!is_array($matcherConfiguration['configurationArray'])) {
                    throw new \RuntimeException(
                        'Configuration array of type ' . get_debug_type($matcherConfiguration['configurationArray'])
                        . ' must be an array',
                        1501509738
                    );
                }
                $configuration = $matcherConfiguration['configurationArray'];
            }

            $matcherInstance = new $matcherConfiguration['class']($configuration);
            if (!$matcherInstance instanceof CodeScannerInterface
                || !$matcherInstance instanceof NodeVisitor
            ) {
                throw new \RuntimeException(
                    'Matcher ' . $matcherConfiguration['class'] . ' must implement CodeScannerInterface'
                    . ' and NodeVisitor',
                    1501510168
                );
            }
            $instances[] = $matcherInstance;
        }
        return $instances;
    }
}
