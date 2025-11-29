# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHPStan Friendly Formatter is a PHPStan extension that provides enhanced error output with syntax-highlighted code context around errors. It transforms standard PHPStan output into a visually clear format showing actual problematic code with line numbers.

## Commands

```bash
composer test          # Run PHPUnit tests
composer cs-fix-dry    # Check code style (dry run)
composer cs-fix        # Fix code style issues
composer analyze       # Run PHPStan with friendly formatter
composer tests         # Run all: cs-fix-dry → analyze → test
```

## Architecture

```
src/
├── FriendlyErrorFormatter.php    # Main entry point, implements PHPStan ErrorFormatter
├── CodeHighlight/
│   ├── CodeHighlighter.php       # Syntax highlighting with version fallback
│   └── FallbackHighlighter.php   # Non-highlighted fallback
├── Config/
│   └── FriendlyFormatterConfig.php  # Formatter configuration (lineBefore, lineAfter, editorUrl)
└── ErrorFormat/
    ├── ErrorWriter.php           # Formats individual errors with code context
    └── SummaryWriter.php         # Error identifier summary statistics
```

**Data Flow:** PHPStan AnalysisResult → FriendlyErrorFormatter → ErrorWriter (with CodeHighlighter) → SummaryWriter → Console output

## Key Configuration Files

- `phpstan.neon.dist` - PHPStan config (level 10, paths, formatter settings)
- `extension.neon` - PHPStan extension registration and parameter schema
- `.php-cs-fixer.dist.php` - Code style rules
- `phpunit.xml` - PHPUnit 10/11 compatible config
- `.tool-versions` - PHP version for local dev and CI (used by `php-version-file` in GitHub Actions)

## Compatibility

- PHP: ^8.1 (tested on 8.1, 8.2, 8.3, 8.4, 8.5)
- PHPStan: ^1.0 || ^2.0
- PHPUnit: ^10.0 || ^11.0 (tests use PHP 8 attributes)
- php-console-highlighter: ^0.3 || ^0.4 || ^0.5 || ^1.0 (with graceful fallback)

The codebase handles multiple dependency versions through class existence checks and fallback implementations.
