<?php

class GoogleConfig
{
    protected static array $config;

    public static function load(): array
    {
        if (!isset(self::$config)) {
            self::$config =
                require ROOT_PATH . 'cfg/google.php';
        }

        return self::$config;
    }

    public static function get(string $key, $default = null)
    {
        $config = self::load();

        $segments = explode('.', $key);

        $value = $config;

        foreach ($segments as $segment) {

            if (!isset($value[$segment])) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}