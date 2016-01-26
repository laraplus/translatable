<?php namespace Laraplus\Data;

class LocaleSettings
{
    protected static $current = 'en';

    protected static $fallback = 'en';

    protected static $available = ['en'];

    protected static $dbSuffix = '_i18n';

    protected static $dbKey = 'locale';

    public static function setCurrent($current)
    {
        static::$current = $current;
    }

    public static function setFallback($fallback)
    {
        static::$fallback = $fallback;
    }

    public static function setAvailable(array $available)
    {
        static::$available = $available;
    }

    public static function setDbSuffix($suffix)
    {
        static::$dbSuffix = $suffix;
    }

    public static function setDbKey($key)
    {
        static::$dbKey = $key;
    }

    public static function current()
    {
        if(static::runsInLaravel()) {
            return app()->getLocale();
        }

        return static::$current;
    }

    public static function fallback()
    {
        if(static::runsInLaravel()) {
            return app()->getFallbackLocale();
        }

        return static::$current;
    }

    public static function available()
    {
        if(static::runsInLaravel()) {
            return config('app.locales', [static::current()]);
        }

        return static::$current;
    }

    public static function dbSuffix()
    {
        return static::$dbSuffix;
    }

    public static function dbKey()
    {
        return static::$dbKey;
    }

    protected static function runsInLaravel()
    {
        return class_exists('\Illuminate\Foundation\Application');
    }
}