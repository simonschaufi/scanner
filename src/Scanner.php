<?php

namespace SimonSchaufi\Scanner;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use SimonSchaufi\Scanner\Domain\Model\DirectoryMatches;
use SimonSchaufi\Scanner\Domain\Model\FileMatches;
use SimonSchaufi\Scanner\Domain\Model\MatcherBundleCollection;
use SimonSchaufi\Scanner\Domain\Model\ScanMatch;
use SimonSchaufi\Scanner\Php\MatcherFactory;
use SimonSchaufi\Scanner\Php\Visitor\CodeStatistics;
use SimonSchaufi\Scanner\Php\Visitor\GeneratorClassesResolver;
use SimonSchaufi\Scanner\Result\Indicator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Scanner
{
    /**
     * Matcher configurations with their files already loaded, see getResolvedMatcherConfigurations().
     *
     * @var list<array<string, mixed>>|null
     */
    private ?array $resolvedMatcherConfigurations = null;

    /**
     * @var array<string, int>
     */
    private array $typeRanking = [
        ScanMatch::TYPE_BREAKING => 100,
        ScanMatch::TYPE_DEPRECATION => 80,
        ScanMatch::TYPE_IMPORTANT => 50,
        ScanMatch::TYPE_FEATURE => 20,
    ];

    public function __construct(
        private readonly Finder $finder,
        private readonly Parser $parser,
        private readonly MatcherFactory $matcherFactory
    ) {
    }

    public function scanPath(
        string $path,
        MatcherBundleCollection $collection
    ): DirectoryMatches {
        if (!is_dir($path)) {
            throw new \RuntimeException(
                sprintf('Path "%s" does not exist or is no directory.', $path),
                1507203233
            );
        }

        $directoryMatches = new DirectoryMatches($path);
        $files = $this->finder
            ->files()
            ->ignoreUnreadableDirs()
            ->in(Utility::removeTrailingDirectorySeparator($path))
            ->name('*.php')
            ->sortByName();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileMatches = $this->scanFile(
                $file->getPathname(),
                $collection
            );
            if ($fileMatches->count() !== 0) {
                $directoryMatches[] = $fileMatches;
            }
        }

        return $directoryMatches;
    }

    public function scanFile(
        string $absoluteFilePath,
        MatcherBundleCollection $collection
    ): FileMatches {
        if (!is_file($absoluteFilePath) || !is_readable($absoluteFilePath)) {
            throw new \RuntimeException(
                'File ' . $absoluteFilePath . ' does not exist or is not readable.',
                1790540102
            );
        }

        try {
            $statements = $this->parser->parse((string)file_get_contents($absoluteFilePath));
        } catch (Error $error) {
            return $this->buildParseExceptionFileMatches($absoluteFilePath, $error);
        }
        if ($statements === null) {
            return new FileMatches($absoluteFilePath, []);
        }

        // The built in NameResolver translates class names shortened with 'use' to fully
        // qualified class names at all places. IMPORTANT: first process completely to resolve
        // fully qualified names of arguments, otherwise GeneratorClassesResolver will NOT get
        // reliable results.
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        // IMPORTANT: second process to actually work on the pre-resolved statements
        $traverser = new NodeTraverser();
        // Understand GeneralUtility::makeInstance('My\\Package\\Foo\\Bar') as fqdn class name
        // in first argument
        $traverser->addVisitor(new GeneratorClassesResolver());
        // Count ignored lines, effective code lines, ...
        $statistics = new CodeStatistics();
        $traverser->addVisitor($statistics);

        $matchers = $this->matcherFactory->createAll($this->getResolvedMatcherConfigurations($collection));
        foreach ($matchers as $matcher) {
            $traverser->addVisitor($matcher);
        }
        $traverser->traverse($statements);

        return new FileMatches($absoluteFilePath, $this->buildMatches($matchers));
    }

    /**
     * The registry declares each matcher with a configuration file. Requiring those 23 files
     * for every scanned file costs seconds over a whole extension and buys nothing, so the
     * loaded configurations are kept and handed to the factory as configurationArray.
     *
     * @return list<array<string, mixed>>
     * @throws \RuntimeException if a declared configuration file is missing or malformed
     */
    private function getResolvedMatcherConfigurations(MatcherBundleCollection $collection): array
    {
        if ($this->resolvedMatcherConfigurations !== null) {
            return $this->resolvedMatcherConfigurations;
        }

        $resolved = $collection->getConfiguration();

        return $this->resolvedMatcherConfigurations = $resolved;
    }

    /**
     * @param CodeScannerInterface[]|NodeVisitor[] $matchers
     * @return ScanMatch[]
     */
    private function buildMatches(array $matchers): array
    {
        $matches = [];
        foreach ($matchers as $matcher) {
            /** @var CodeScannerInterface $matcher */
            $matcherName = $this->getShortClassName($matcher::class);
            foreach ($matcher->getMatches() as $match) {
                $restFiles = array_values($match['restFiles']);
                $type = null;
                if ($restFiles !== []) {
                    $type = $this->inferTypeFromRestFiles($restFiles);
                }
                $scanMatch = new ScanMatch(
                    $matcherName,
                    Indicator::from($match['indicator']),
                    $match['message'],
                    $match['line'],
                    $restFiles,
                    $type ?? ScanMatch::TYPE_IMPORTANT
                );
                $matches[] = $scanMatch;
            }
        }

        return $matches;
    }

    private function buildParseExceptionFileMatches(
        string $file,
        Error $error
    ): FileMatches {
        $match = new ScanMatch(
            $error::class,
            Indicator::from('strong'),
            $error->getMessage(),
            $error->getStartLine(),
            [],
            ScanMatch::TYPE_BREAKING
        );
        return new FileMatches($file, [$match]);
    }

    private function getShortClassName(string $fullyQualifiedClassName): string
    {
        $separatorPosition = strrpos($fullyQualifiedClassName, '\\');

        return $separatorPosition === false
            ? $fullyQualifiedClassName
            : substr($fullyQualifiedClassName, $separatorPosition + 1);
    }

    /**
     * @param list<string> $restFiles
     */
    private function inferTypeFromRestFiles(array $restFiles): string
    {
        $type = '';
        foreach ($restFiles as $restFile) {
            $fileType = $this->extractFileType($restFile);
            $type = $this->getHigherType($type, $fileType);
            if ($type === ScanMatch::TYPE_BREAKING) {
                // breaking is the highest type, return if this type is found.
                break;
            }
        }
        return $type;
    }

    private function extractFileType(string $restFile): string
    {
        [$fileType] = explode('-', basename($restFile));
        return strtoupper($fileType);
    }

    private function getHigherType(string $type, string $fileType): string
    {
        if ($type === '') {
            return $fileType;
        }
        return ($this->typeRanking[$fileType] > $this->typeRanking[$type]) ? $fileType : $type;
    }
}
