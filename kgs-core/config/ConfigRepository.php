<?php

class ConfigRepository
{
    private static array $cache = [];

    public static function get(string $key, $default = null)
    {
        // 1. memory cache first
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        // 2. file config fallback
        $value = config_value($key, $default);

        // 3. store in memory
        self::$cache[$key] = $value;

        return $value;
    }

    public static function setRuntime(string $key, $value): void
    {
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        global $config;
        return $config ?? [];
    }
	
	
	public static function overrideFromGoogleSheet(array $rows): void
	{
		foreach ($rows as $row) {

			$key = $row[0] ?? null;
			$value = $row[1] ?? null;

			if (!$key) continue;

			self::$cache[$key] = $value;
		}
	}


}