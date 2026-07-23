<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

/**
 * Reads and writes individual keys in the application's .env file.
 * Used by settings that must live in .env (e.g. OAuth credentials that
 * config/services.php resolves via env()).
 */
final class EnvService
{
    /**
     * Current value of a key straight from the .env file (bypasses any
     * cached config), or the given default when the key is absent.
     */
    public function get(string $key, string $default = ''): string
    {
        $path = app()->environmentFilePath();

        if (! is_file($path)) {
            return $default;
        }

        $content = (string) file_get_contents($path);

        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $content, $matches)) {
            return $this->unquote(trim($matches[1]));
        }

        return $default;
    }

    /**
     * Write/replace one or more key => value pairs, then refresh the config
     * cache so the new values take effect on the next request.
     *
     * @param  array<string, string>  $values
     */
    public function set(array $values): void
    {
        $path = app()->environmentFilePath();

        if (! is_file($path)) {
            return;
        }

        $content = (string) file_get_contents($path);

        foreach ($values as $key => $value) {
            $content = $this->writeKey($content, $key, (string) $value);
        }

        file_put_contents($path, $content);

        // Ensure a cached config (if any) does not shadow the new values.
        Artisan::call('config:clear');
    }

    private function writeKey(string $content, string $key, string $value): string
    {
        $line = $key.'='.$this->quote($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $content)) {
            return (string) preg_replace($pattern, $line, $content, 1);
        }

        return rtrim($content, "\r\n")."\n".$line."\n";
    }

    /**
     * Quote values that contain characters .env cannot hold bare.
     */
    private function quote(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s"\'#=]/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    private function unquote(string $value): string
    {
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);

            return str_replace('\"', '"', $value);
        }

        return $value;
    }
}
