# AGENTS.md

## What this is

A PHP library (`simonschaufi/typo3-cms-scanner`, see `composer.json`) that
statically analyses PHP files for usage of code that TYPO3 deprecated or removed.
It parses files with `nikic/php-parser` into an AST and runs a set of
*matchers* (php-parser node visitors) over it, each driven by a *rule set* —
a plain PHP array of deprecated symbols.
It is a library only: it ships no CLI.
Consumers build the surrounding tool (see `examples/test.php`, or the
[standalone scanner](https://github.com/simonschaufi/typo3scan-src) linked from `Readme.md`).

Matcher classes in `src/Php/Matcher/` and visitors in `src/Visitor/` are ports of
TYPO3 core's EXT:install extension scanner
(hence the `@internal … only meant to be used within EXT:install` docblocks).
Keeping them in sync with upstream core is a recurring task — see commits like
`[FEATURE] Update code with changes in TYPO3 core`.
Commit subjects follow the TYPO3 convention: `[FEATURE]`, `[TASK]`, `[BUGFIX]`
(upstream also uses `!!!` for breaking changes).

**`TYPO3\…` strings elsewhere in the repo are data, not code**: every key in
`config/Matcher/**` and most of `tests/**/Fixtures/` names a TYPO3 *core* class
that the scanner searches for.
A blanket search-and-replace across `TYPO3\` would silently destroy the rule sets.

Three *real* `TYPO3\…` references exist in code, all intentional:

- `src/Visitor/GeneratorClassesResolver.php` imports `TYPO3\CMS\Core\Utility\GeneralUtility`
  and compares against `GeneralUtility::class` — this is upstream's own code, not a
  fork quirk. It only needs the class *name*, never an instance.
- `stubs/TYPO3/CMS/Core/Utility/GeneralUtility.php` and
  `stubs/TYPO3/CMS/Core/Site/Set/SetRegistry.php` are dev-only stubs, autoloaded via
  `autoload-dev` (`"TYPO3\\": "stubs/TYPO3"`), used by the test suite.
- Nothing else in `src/` may import `TYPO3\…` — the library does not depend on TYPO3.

The library has its own `M12R\Scanner\Utility\GeneralUtility::trimExplode()`
(`src/Utility/GeneralUtility.php`), a small local helper, not a TYPO3 class.

## Commands

```bash
composer install
vendor/bin/phpunit tests                                   # whole suite (or just `vendor/bin/phpunit`, see phpunit.xml.dist)
vendor/bin/phpunit tests/Unit/Php/Matcher/MethodCallMatcherTest.php   # one file
vendor/bin/phpunit --filter hitsFromFixtureAreFound tests   # one test
vendor/bin/phpstan analyse                                 # level 9, config in phpstan.neon
vendor/bin/ecs check                                       # config in ecs.php
vendor/bin/ecs check --fix                                 # apply code style fixes
```

Tests use PHPUnit 12 attributes (`#[Test]`, `#[DataProvider]`).
Data provider methods must be `static`.
The suite has **1 skip**: `MatcherFactoryTest::createAllThrowsIfConfigurationFileDoesNotReturnArray`,
a bare `markTestSkipped()` because the referenced fixture path does not exist.

## Known state / gotchas

- `Readme.md` is stale in three places: the matcher list still predates
  `AbstractMethodImplementationMatcher`, `ConstructorArgumentMatcher`,
  `MethodCallArgumentValueMatcher` and `ScalarStringMatcher`; it claims rules exist
  only "for v7, v8 and v9"; and its test command is `./.Build/bin/phpunit tests`.

## Architecture

Scan pipeline, from the outside in:

1. **`MatcherBundle`** (`src/Domain/Model/`) is the unit of configuration: a rule-set
   directory (e.g. `config/Matcher/v13`), a documentation path, and an **array** of
   matcher class names.
   For each class it derives
   `configurationFile = <configPath>/<ShortClassName>.php`. **The rule file's basename
   must equal the matcher class's short name** — that mapping is the only link between
   the two.
2. **`MatcherBundleCollection`** merges several bundles (e.g. v12 + v13 rules) into one
   flat configuration array.
3. **`Scanner::scanPath()`** walks `*.php` under a directory with symfony/finder and
   calls `scanFile()` per file. Matchers accumulate state, so
   `TraverserFactory::createMatchers()` builds a **fresh matcher instance for every
   file**.
4. The visitor chain is assembled in **`Scanner::scanFile()`**, as *two* traversals —
   order matters:
    1. `NameResolver` alone, so `use` statements are expanded to FQCNs *everywhere*
       before anything else reads names;
    2. `GeneratorClassesResolver` (turns `makeInstance('Foo\Bar')` string arguments into
       `FullyQualified` nodes) → `CodeStatistics` (counts effective and ignored lines) →
       all matchers.
       `TraverserFactory` only builds matcher instances (`createMatchers()`); there is no
       `createTraverser()` anymore.
5. **`MatcherFactory::createAll()`** (`src/Php/MatcherFactory.php`, the *live* factory)
   instantiates matchers, requiring exactly one of `configurationFile` /
   `configurationArray`, and runtime-checks that each instance is both a
   `CodeScannerInterface` and a `NodeVisitor`.
6. Each matcher pushes raw arrays onto `$this->matches` with keys `restFiles`, `line`,
   `message`, `indicator` — typed as `MatchArray` on `CodeScannerInterface`.
7. **`Scanner::buildMatches()`** converts those into `Domain\Model\ScanMatch` objects.
   Severity is *inferred from the `.rst` filename prefix* —
   `Breaking-…` / `Deprecation-…` / `Important-…` / `Feature-…` — and when a match cites
   several files the highest wins (BREAKING 100 > DEPRECATION 80 > IMPORTANT 50 >
   FEATURE 20), defaulting to `IMPORTANT`. A parse error yields a single BREAKING match
   with indicator `strong` instead of aborting (`Result\Indicator` is an enum with only
   `Strong` and `Weak`).

Two cross-cutting matcher behaviours live in `AbstractCoreMatcher`:
the `@extensionScannerIgnoreLine` / `@extensionScannerIgnoreFile` annotations
(every `enterNode()` must guard on `isFileIgnored()` / `isLineIgnored()` **before**
its node-type check, because the annotation lives on the enclosing statement and the
guards maintain per-line state), and `initializeFlatMatcherDefinitions()`, which builds
the method-name-keyed "candidates" lookup used for **weak** matches.
Matchers report `indicator => 'strong'` when the class is fully qualified and
resolvable, `'weak'` when only the method/property name matched — consumers are
expected to treat weak hits as needing human review.

## Rule sets (`config/Matcher/`)

One directory per target TYPO3 version, `v7` through `v14`. Each file is a plain PHP
`return [...]`, keyed by the symbol being matched — `Foo\Bar::method`, `Foo\Bar->method`,
a bare class or constant name, a regex pattern (`ClassNamePatternMatcher`), or a literal
`$GLOBALS['…']['…']` expression, depending on the matcher. Every entry must carry a
non-empty `restFiles` array; matchers declare additional required keys via
`validateMatcherDefinitions()` in their constructor (e.g. `MethodCallStaticMatcher`
requires `numberOfMandatoryArguments` and `maximumNumberOfArguments`), and a missing
key throws at construction time.

The set of files differs per version:

- `v12` adds `ScalarStringMatcher`,
- `v13` adds `MethodCallArgumentValueMatcher` and `AbstractMethodImplementationMatcher`,
- `v13` and `v14` have identical file lists,
- only `v7` has the four fork-local matchers `ArrayMatcher`, `ClassNamePatternMatcher`,
  `GlobalMatcher`, `MethodArgumentUnusedStaticMatcher` — **v8 has none of them**.

A bundle lists its matcher classes explicitly, so only reference classes whose rule file
exists in that version directory.

## Tests

`tests/Unit/Php/Matcher/<Matcher>Test.php` pairs with
`tests/Unit/Php/Matcher/Fixtures/<Matcher>Fixture.php`.
Each test parses the fixture, runs the traverser, and asserts the **exact line numbers**
the matcher hit.
Editing a fixture shifts those numbers — update the expectations in the same change.
New or extended matchers are expected to come with both files.

Conventions worth following:

- Append fixture cases at the **end** of the fixture's method, so the exact-line-number
  expectations of existing tests don't shift.
- Run with `NameResolver` first; add `GeneratorClassesResolver` only when the test needs
  string class names (`makeInstance('…')`) resolved.
- Some matchers have extra tests asserting `assertEmpty($subject->getMatches())` for
  configurations that must not hit; a few fixtures are numbered
  (`PropertyExistsStaticMatcherFixture1..5`) plus a shared `Fixtures/Subject.php`.
- Non-matcher tests live directly in `tests/Unit/Php/` (`CodeStatisticsTest`,
  `GeneratorClassesResolverTest`, `MatcherFactoryTest`).
- Prove a new test actually guards a change: revert the `src/` edit, confirm the test
  fails, restore it, confirm the suite is green again.

## Syncing with TYPO3 core

The **rule sets** (`config/Matcher/vNN`) and the **classes** (`src/`) are two independent
jobs; keep them in separate commits.

### Fetching upstream

Pin a release branch, never `main` — `main` carries rules for the next unreleased
version. Available release branches are `14.0`, `14.1`, `14.3` (no `14.4`); **the current
v14 files actually track `main`**, not `14.3`.

```bash
# Listings via gh (needs valid credentials; the local `gh` currently fails with HTTP 401.
# Unauthenticated api.github.com + raw.githubusercontent.com work fine.)
gh api "repos/TYPO3/typo3/contents/typo3/sysext/install/Configuration/ExtensionScanner/Php?ref=14.3" --jq '.[].name'
gh api "repos/TYPO3/typo3/git/trees/14.3?recursive=1" \
    --jq '.tree[] | select(.path | startswith("typo3/sysext/install/Classes/ExtensionScanner/")) | .path'
# File contents via raw.githubusercontent.com
curl -sS "https://raw.githubusercontent.com/TYPO3/typo3/14.3/<path>" -o <target>
```

Always anchor tree filters on `Classes/…`.
A bare `ExtensionScanner/Php/Matcher` pattern also matches the `Tests/` tree
and will silently pull in test fixtures.

- rules → `typo3/sysext/install/Configuration/ExtensionScanner/Php/<Matcher>.php`
- classes → `typo3/sysext/install/Classes/ExtensionScanner/`:
  `CodeScannerInterface.php`, `ExtensionScannerService.php`,
  `Php/CodeStatistics.php`, `Php/GeneratorClassesResolver.php`, `Php/MatcherFactory.php`,
  `Php/MatcherRegistry.php`, `Php/Matcher/*.php`, `Result/{Indicator,ScanMatch,ScanResult}.php`.
  Local counterparts exist for everything except `ExtensionScannerService.php` and
  `Php/MatcherRegistry.php`.

### Updating rule sets

Compare sizes to find candidate files, then work at *key* level, never text level:

1. Parse both sides with php-parser from `vendor` and walk `Return_ → Array_ → items`;
   the key is `$item->key->value` and the source span is `getStartLine()`/`getEndLine()`.
   Classify every key as **missing** (upstream only), **changed** (present both sides,
   different value), or **local-only**.
2. Prove the divergence lives entirely inside entries: blank out every item's line span on
   both sides and diff the remainder.
   A zero diff means the headers, section comments (`// Removed classes`) and trailing
   `/** Note (leave this at the bottom) */` blocks agree — only then is rebuilding a file
   from upstream text safe.
3. Rebuild each differing file from the upstream text, then re-apply whatever the request
   preserves (local values for changed keys, local-only keys re-inserted after their local
   predecessor). This keeps upstream's grouping, so new `PageRenderer` rules land beside
   the existing ones. **Never regenerate with `var_export()`** — it reformats every entry
   and buries the real change.
4. Verify programmatically, not by eye: every pre-existing entry byte-identical unless
   deliberately updated, every upstream entry present with upstream text *and* value, no
   invented or duplicated keys, `php -l` per file.

**"Add the missing rules" and "update the changed rules" are different requests — ask
which is meant.** Changed entries can alter scan output rather than just docs:
the `sanitizeLocalUrl` update raised `numberOfMandatoryArguments` from 1 to 2,
so single-argument calls begin reporting.

### Updating classes

Diff with namespaces normalised onto neutral placeholders on both sides, longest prefix first:

| upstream                                          | local                       |
|---------------------------------------------------|-----------------------------|
| `TYPO3\CMS\Install\ExtensionScanner\Php\Matcher`  | `M12R\Scanner\Php\Matcher`  |
| `TYPO3\CMS\Install\ExtensionScanner\Php`          | `M12R\Scanner\Visitor`      |
| `TYPO3\CMS\Install\ExtensionScanner\Php`          | `M12R\Scanner\Php`          |
| `TYPO3\CMS\Install\ExtensionScanner\Result`       | `M12R\Scanner\Result`       |
| `TYPO3\CMS\Install\ExtensionScanner`              | `M12R\Scanner`              |

The two `Php` rows are ambiguous: `CodeStatistics` and `GeneratorClassesResolver` live in
`M12R\Scanner\Visitor`, while `MatcherFactory` lives in `M12R\Scanner\Php`. Resolve per file.

When a file's only remaining difference *is* the change being ported, copy upstream and
rewrite the single `namespace` line instead of hand-editing:

```bash
perl -pe 's{^namespace TYPO3\\CMS\\Install\\ExtensionScanner\\Php\\Matcher;$}{namespace M12R\\Scanner\\Php\\Matcher;}' \
    <upstream-file> > src/Php/Matcher/<Matcher>.php
```

**Caveat:** local ports carry deliberate style/logic deviations (below), so a naive copy
reverts them. Re-check each copied file against the "Known local deviations" list.

#### Known local deviations (must survive a sync)

- `getStartLine()` / `getComments()` instead of `getAttribute('startLine')` /
  `getAttribute('comments')`; typed properties; `: void` return types; `@phpstan-*`
  types (`MatchArray`, `MatcherDefinition`, …); the license header reads
  "or (at your option) any later version".
- `AbstractCoreMatcher::isArgumentUnpackingUsed()` also handles `ArgPlaceholder`
  (php-parser 5 partial application), which upstream does not.
- `ConstructorArgumentMatcher` uses a typed `$topicMatcherDefinitions` property and
  extra `=== null` guards around the four topic lookups.
- `GeneratorClassesResolver` carries an extra `$node->name instanceof Identifier` guard.
- `src/Php/MatcherFactory.php` uses `configurationFile` **as-is** instead of
  `GeneralUtility::getFileAbsFileName()`, and drops core's
  `use TYPO3\CMS\Core\Utility\GeneralUtility` import.
- `M12R\Scanner\Utility\GeneralUtility::trimExplode()` replaces core's
  `GeneralUtility::trimExplode()` (used by `AbstractCoreMatcher`,
  `AbstractMethodImplementationMatcher`, `ArrayDimensionMatcher`).
- `src/CodeScannerInterface.php` matches core: it does **not** extend `NodeVisitor`
  anymore (the local `extends NodeVisitor` was dropped). `MatcherFactory::createAll()`
  enforces the combination at runtime instead; `TraverserFactory::createMatchers()`
  is typed `list<CodeScannerInterface&NodeVisitor>`.
- The four matchers core deleted but the `v7` rule set still needs: `ArrayMatcher`,
  `ClassNamePatternMatcher`, `GlobalMatcher`, `MethodArgumentUnusedStaticMatcher`.
  They have no upstream counterpart, so apply core's matcher-wide fixes to them by hand.

Nothing else in `src/Php/Matcher/` and `src/` should deviate from core. A normalized diff
(namespace + `@internal` line + the list above) that shows further changes means something
was missed or wrongly ported.

### Covering a ported behaviour change

- Append fixture cases at the **end** of the fixture's method, so the exact-line-number
  expectations of existing tests don't shift.
- Prove a new test actually guards the change: revert the `src/` edit, confirm the test
  fails, restore it, confirm the suite is green again.

### Crib sheet for scratch scripts

- autoload `vendor/autoload.php`
- `ScannerFactory::create()->createFor()` — `createFor()` is an instance method and takes no arguments
- `Scanner::scanFile($file, $collection)` — the bundle collection is a per-call argument
- `Domain\Model\ScanMatch::getType()` returns the severity; there is no `getSeverity()`
- grepping for `$node->name->name` needs `grep -F`
