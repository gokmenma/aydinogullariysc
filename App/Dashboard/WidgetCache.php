<?php

namespace App\Dashboard;

class WidgetCache
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?: dirname(__DIR__, 2) . '/storage/cache/dashboard_widgets';
    }

    public function get(string $key, int $ttl, bool $allowStale = false): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }

        if (!$allowStale && (time() - (int)filemtime($path)) > $ttl) {
            return null;
        }

        $payload = json_decode((string)@file_get_contents($path), true);
        return is_array($payload) ? $payload : null;
    }

    public function put(string $key, array $value): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            return;
        }

        $path = $this->path($key);
        $temporary = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($temporary, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false) {
            @rename($temporary, $path);
        }
        if (is_file($temporary)) {
            @unlink($temporary);
        }
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
