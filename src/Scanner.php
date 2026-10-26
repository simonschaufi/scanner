<?php

namespace M12R\Scanner;

use M12R\Scanner\Domain\Model\DirectoryMatches;
use M12R\Scanner\Domain\Model\FileMatches;
use M12R\Scanner\Domain\Model\MatcherBundleCollection;
use M12R\Scanner\Domain\Model\ScanMatch;
use M12R\Scanner\Result\Indicator;
use M12R\Scanner\Visitor\TraverserFactory;
use PhpParser\Error;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Scanner
{
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
        private readonly TraverserFactory $traverserFactory
    ) {
    }

    public function scanPath(
        string $path,
        MatcherBundleCollection $collection
    ): DirectoryMatches {
        if (!is_dir($path)) {
            throw new \RuntimeException(
                sprintf('Path "%s" does not exist', $path),
                1507203233
            );
        }

        $directoryMatches = new DirectoryMatches($path);
        $files = $this->finder->files()
            ->in(Utility::removeTrailingDirectorySeparator($path))
            ->name('*.php');

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
        string $file,
        MatcherBundleCollection $collection
    ): FileMatches {
        if (!file_exists($file)) {
            throw new \RuntimeException(
                sprintf('File "%s" does not exist', $file),
                1507203234
            );
        }

        try {
            $statements = $this->parser->parse((string)file_get_contents($file));
        } catch (Error $error) {
            return $this->buildParseExceptionFileMatches($file, $error);
        }
        if ($statements === null) {
            return new FileMatches($file);
        }

        $matchers = $this->traverserFactory->createMatchers($collection);

        $traverser = $this->traverserFactory->createTraverser($matchers);
        $traverser->traverse($statements);

        return new FileMatches($file, ...$this->buildMatches($matchers));
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
        return new FileMatches($file, $match);
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
