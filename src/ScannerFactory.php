<?php

namespace M12R\Scanner;

use M12R\Scanner\Matcher\MatcherFactory;
use M12R\Scanner\Visitor\TraverserFactory;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Finder\Finder;

class ScannerFactory
{
    private ParserFactory $parserFactory;
    private TraverserFactory $traverserFactory;

    public static function create(): static
    {
        return new static(
            new ParserFactory(),
            new TraverserFactory(
                new MatcherFactory()
            )
        );
    }

    public function __construct(
        ParserFactory $parserFactory,
        TraverserFactory $traverserFactory
    ) {
        $this->parserFactory = $parserFactory;
        $this->traverserFactory = $traverserFactory;
    }

    public function createFor(
    ): Scanner {
        $finder = new Finder();
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));

        return new Scanner(
            $finder,
            $parser,
            $this->traverserFactory
        );
    }
}
