class Settings
{
    public static function get(string $key, $default = null)
    {
        return config($key, $default);
    }
}