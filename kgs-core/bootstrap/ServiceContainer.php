<?php
/**
 * SERVICE CONTAINER REGISTRY
 *
 * Responsibility:
 * - Provides global access to core services
 * - Binds CMS service implementation (ContentCMSService)
 *
 * Rules:
 * - MUST NOT contain business logic
 * - MUST NOT load CMS content directly
 */
 
class ServiceContainer
{
    private static array $services = [];
    private static array $factories = [];

    public static function set(string $key, $value): void
    {
        self::$services[$key] = $value;
    }

    public static function factory(string $key, callable $factory): void
    {
        self::$factories[$key] = $factory;
    }

    public static function get(string $key)
    {
        if (isset(self::$services[$key])) {
            return self::$services[$key];
        }

        if (isset(self::$factories[$key])) {
            self::$services[$key] = (self::$factories[$key])();
            return self::$services[$key];
        }

        return null;
    }
}