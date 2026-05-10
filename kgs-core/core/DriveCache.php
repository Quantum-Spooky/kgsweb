<?php

class DriveCache
{
    public static function get(string $key, int $ttl = 3600)
    {
        $file = self::path($key);

        if (!file_exists($file)) return null;

        if (time() - filemtime($file) > $ttl) {
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    public static function set(string $key, array $data)
    {
        $file = self::path($key);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, json_encode($data));
    }

    private static function path(string $key)
    {
        return ROOT_PATH . 'kgs-cache/pages/' . $key . '.json';
    }
}