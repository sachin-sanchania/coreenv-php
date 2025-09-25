<?php
declare(strict_types=1);
namespace CoreEnv;
use CoreEnv\Exception\EnvException;

/**
 * Simple, immutable env loader for core PHP (Laravel-style)
 *
 * Features:
 *  - Load .env, .env.local, .env.{env} merge (local overrides)
 *  - Populate putenv(), $_ENV and $_SERVER
 *  - Typed getters (string,int,bool,float,arrays)
 *  - Required variable validation
 *  - Basic value parsing for quoted strings and booleans
 */
final class Env
{
    private static ?self $instance = null;
    private array $vars = [];
    private string $path;
    private function __construct(string $path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->loadFiles();
    }

    public static function getInstance(string $path = __DIR__ . '/../'): self
    {
        if (self::$instance === null) {
            self::$instance = new self($path);
        }
        return self::$instance;
    }

    private function loadFiles(): void
    {
        $base = $this->path;
        $files = [
            $base . '.env',
            $base . '.env.' . ($this->getServerEnv() ?? ''),
            $base . '.env.local',
        ];

        // If APP_ENV is set in server environ, ensure that env-specific file loads
        // We'll collect in order and later merge so later files override earlier ones.
        $loaded = [];
        foreach ($files as $file) {
            if (file_exists($file)) {
                $parsed = $this->parseFile($file);
                $loaded[] = $file;
                $this->vars = array_merge($this->vars, $parsed);
            }
        }

        // Push to PHP environment arrays
        foreach ($this->vars as $k => $v) {
            // do not overwrite existing env values if present
            if (getenv($k) === false) {
                putenv(sprintf('%s=%s', $k, (string)$v));
            }
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }

    private function parseFile(string $file): array
    {
        $result = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            // Support KEY="value" or KEY='value' or KEY=value
            if (!str_contains($line, '=')) continue;
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // strip quotes
            if ((str_starts_with($value,'"') && str_ends_with($value,'"')) ||
                (str_starts_with($value,"'") && str_ends_with($value,"'"))) {
                $value = substr($value,1,-1);
            }
            // parse booleans and null
            $lower = strtolower($value);
            if (in_array($lower, ['true','false','null','empty'], true)) {
                if ($lower === 'true') $value = 'true';
                if ($lower === 'false') $value = 'false';
                if ($lower === 'null') $value = '';
                if ($lower === 'empty') $value = '';
            }
            $result[$name] = $value;
        }
        return $result;
    }

    private function getServerEnv(): ?string
    {
        // prefer APP_ENV from server/ENV if present
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null);
        return $env;
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->vars[$key] ?? $default;
    }

    public function getString(string $key, string $default = ''): string
    {
        $v = $this->get($key, $default);
        return (string)$v;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $v = $this->get($key, $default);
        return (int)$v;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $v = $this->get($key, $default);
        return (float)$v;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $v = $this->get($key, $default);
        if (is_bool($v)) return $v;
        $lower = strtolower((string)$v);
        return in_array($lower, ['1','true','on','yes'], true);
    }

    /**
     * Parse comma separated list into array trim values
     */
    public function getArray(string $key, array $default = []): array
    {
        $v = $this->get($key, null);
        if ($v === null || $v === '') return $default;
        return array_map('trim', explode(',', (string)$v));
    }

    /**
     * Ensure variables exist - throws EnvException on missing
     * @param array $keys
     * @throws EnvException
     */
    public function requireVars(array $keys): void
    {
        $miss = [];
        foreach ($keys as $k) {
            if (!isset($this->vars[$k]) || $this->vars[$k] === '') {
                $miss[] = $k;
            }
        }
        if (!empty($miss)) {
            throw new EnvException('Missing required env vars: ' . implode(', ', $miss));
        }
    }
}
