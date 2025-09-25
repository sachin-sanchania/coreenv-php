# CoreEnv PHP — Lightweight .env for Core PHP 8+

**CoreEnv PHP** is a minimal, Laravel-style environment variable loader and manager for **Core PHP 8+** applications.
It provides `.env` loading, environment merging (local overrides), typed accessors, and simple validation — all without framework dependencies.

## Features
- Load `.env`, `.env.{env}` and `.env.local` with override order.
- Populate `putenv()`, `$_ENV` and `$_SERVER`.
- Typed getters: `getString`, `getInt`, `getBool`, `getFloat`, `getArray`.
- Require/validate keys via `requireVars()`.
- Small, zero-dependency library for legacy and modern PHP apps.
- Safe default: does not overwrite existing environment entries.

## Quick Start

```bash
composer require sachin-sanchania/coreenv-php
```

**Example**
```php
require 'vendor/autoload.php';
use CoreEnv\Env;

// Initialize (path to folder containing .env files)
$env = Env::getInstance(__DIR__ . '/');

echo $env->getString('APP_NAME', 'MyApp');
echo $env->getBool('APP_DEBUG') ? 'debug' : 'prod';
```

## .env priority & merging
Loader reads files in this order and later files override earlier values:

1. `.env` (base)
2. `.env.{APP_ENV}` (e.g. `.env.production`)
3. `.env.local` (developer override)

## API
- `Env::getInstance(string $path = __DIR__ . '/../')` — returns singleton instance.
- `get(string $key, $default = null)`
- `getString(string $key, string $default = '')`
- `getInt(string $key, int $default = 0)`
- `getFloat(string $key, float $default = 0.0)`
- `getBool(string $key, bool $default = false)`
- `getArray(string $key, array $default = [])`
- `requireVars(array $keys)` — throws if missing.

## Example project
See `example/` for `.env`, `.env.local`, `.env.prod` and a small `index.php` demonstrating usage.

## Security & Best Practices
- Do **not** commit `.env` files with secrets to version control. Use `.env.example` to document variables.
- Keep production `.env` files outside your webroot, and protect them with correct file permissions.
- For CI/CD or container deployments, prefer server-level environment variables (not files) for secrets.

## Contributing
PRs welcome. Please follow coding standards and add tests.

## License
MIT
