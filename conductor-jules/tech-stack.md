# Tech Stack: LetMeDown

## Core Technologies
- **Language**: PHP 8.1+
- **Markdown Engine**: `erusev/parsedown` (Main dependency)
- **Document Processing**: `DOMDocument` for marker and field extraction.

## Development Environment
- **Platform**: DDEV
- **PHP Access**: via `ddev php`
- **Dependency Management**: Composer

## Testing Framework
- **Engine**: PHPUnit 9
- **Command**: `ddev composer test`
- **CI**: GitHub Actions (Release Please)

## Key Files
- `src/LetMeDown.php`: Monolithic library file.
- `tests/`: PHPUnit test suite.
