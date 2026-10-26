<?php

namespace SimonSchaufi\Scanner;

use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use SimonSchaufi\Scanner\Php\MatcherFactory;
use SimonSchaufi\Scanner\Visitor\TraverserFactory;
use Symfony\Component\Finder\Finder;

final readonly class ScannerFactory
{
    public static function create(): self
    {
        return new self(
            new ParserFactory(),
            new TraverserFactory(
                new MatcherFactory()
            )
        );
    }

    public function __construct(
        private ParserFactory $parserFactory,
        private TraverserFactory $traverserFactory
    ) {
    }

    public function createFor(): Scanner
    {
        $finder = new Finder();
        $parser = $this->parserFactory->createForVersion(PhpVersion::fromComponents(8, 2));

        return new Scanner(
            $finder,
            $parser,
            $this->traverserFactory
        );
    }
}
