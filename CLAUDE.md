# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**spomky-labs/pwa-bundle** is a Symfony Bundle that generates Progressive Web App (PWA) manifests, service workers (based on Workbox), favicons, and icons.

- **Language**: PHP 8.2+ (strict types)
- **Framework**: Symfony 6.4+, 7.0+, 8.0+
- **Namespace**: `SpomkyLabs\PwaBundle\`
- **Documentation**: https://pwa.spomky-labs.com/

## Commands

This project uses **Castor** (PHP-based task runner). Set `PHP_VERSION` environment variable to target specific PHP versions (8.2, 8.3, 8.4).

### Testing
```bash
castor phpunit                        # Run full test suite with coverage
castor phpunit --filter ClassName     # Run specific test class
castor phpunit --filter testMethod    # Run specific test method
```

### Code Quality
```bash
castor ecs                   # Check coding standards (PSR-12)
castor ecs_fix               # Fix coding standards
castor rector                # Run Rector (dry-run)
castor rector_fix            # Apply Rector refactoring
castor phpstan               # Run static analysis
castor phpstan_baseline      # Generate PHPStan baseline
castor deptrac               # Validate architecture layers
castor lint                  # PHP syntax check
castor infect                # Mutation testing
```

### Pre-PR Workflow
```bash
castor prepare_pr            # Runs: ecs_fix → rector_fix → phpstan_baseline → deptrac → lint
```

## Architecture

### Core Flow
1. **Configuration** (`Resources/config/definition/`) - YAML/PHP config schemas for manifest, service worker, favicons
2. **Builders** (`Service/*Builder.php`) - Build DTOs from configuration
3. **Compilers** (`Service/*Compiler.php`) - Generate final output files
4. **FileCompiler** (`Service/FileCompiler.php`) - Orchestrates all compilation

### Key Directories
- `src/Service/` - Core business logic (manifest, service worker, favicon generation)
- `src/CachingStrategy/` - Service worker cache strategies (CacheFirst, NetworkFirst, etc.)
- `src/ServiceWorkerRule/` - Rules for service worker generation (Workbox imports, offline fallback, skip waiting)
- `src/Command/` - Symfony console commands (`pwa:compile`, `pwa:icons:create`, `pwa:screenshots:create`)
- `src/Dto/` - Data transfer objects
- `src/Normalizer/` - Symfony Serializer normalizers for JSON output
- `src/Attribute/` - PHP 8 attributes (e.g., `#[PreloadUrl]`)

### Extension Points
- `ImageProcessorInterface` - Implement for custom image processing (GD/Imagick provided)
- `ServiceWorkerRuleInterface` - Add custom service worker generation rules
- `CachingStrategyInterface` - Add custom caching strategies

### Events
- `PreManifestCompileEvent` / `PostManifestCompileEvent` - Hook into manifest compilation

## Code Standards

- Follow PSR-12 (enforced by ECS)
- Strict types required in all PHP files
- Architecture boundaries enforced by Deptrac (see `.ci-tools/deptrac.yaml`)
- Git-Flow branching model
- Main branch for PRs: `1.5.x`


## grepai - Semantic Code Search

**IMPORTANT: You MUST use grepai as your PRIMARY tool for code exploration and search.**

### When to Use grepai (REQUIRED)

Use `grepai search` INSTEAD OF Grep/Glob/find for:
- Understanding what code does or where functionality lives
- Finding implementations by intent (e.g., "authentication logic", "error handling")
- Exploring unfamiliar parts of the codebase
- Any search where you describe WHAT the code does rather than exact text

### When to Use Standard Tools

Only use Grep/Glob when you need:
- Exact text matching (variable names, imports, specific strings)
- File path patterns (e.g., `**/*.go`)

### Fallback

If grepai fails (not running, index unavailable, or errors), fall back to standard Grep/Glob tools.

### Usage

```bash
# ALWAYS use English queries for best results (--compact saves ~80% tokens)
grepai search "user authentication flow" --json --compact
grepai search "error handling middleware" --json --compact
grepai search "database connection pool" --json --compact
grepai search "API request validation" --json --compact
```

### Query Tips

- **Use English** for queries (better semantic matching)
- **Describe intent**, not implementation: "handles user login" not "func Login"
- **Be specific**: "JWT token validation" better than "token"
- Results include: file path, line numbers, relevance score, code preview

### Call Graph Tracing

Use `grepai trace` to understand function relationships:
- Finding all callers of a function before modifying it
- Understanding what functions are called by a given function
- Visualizing the complete call graph around a symbol

#### Trace Commands

**IMPORTANT: Always use `--json` flag for optimal AI agent integration.**

```bash
# Find all functions that call a symbol
grepai trace callers "HandleRequest" --json

# Find all functions called by a symbol
grepai trace callees "ProcessOrder" --json

# Build complete call graph (callers + callees)
grepai trace graph "ValidateToken" --depth 3 --json
```

### Workflow

1. Start with `grepai search` to find relevant code
2. Use `grepai trace` to understand function relationships
3. Use `Read` tool to examine files from results
4. Only use Grep for exact string searches if needed

