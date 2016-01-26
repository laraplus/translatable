<?php namespace Laraplus\Data;

use Exception;

class TranslatableConfig
{
    protected static $current = 'en';

    protected static $fallback = 'en';

    protected static $available = ['en'];

    protected static $dbSuffix = '_i18n';

    protected static $dbKey = 'locale';

    protected static $cacheGetter = null;

    protected static $cacheSetter = null;

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

    public static function cacheGetter(callable $getter)
    {
        static::$cacheGetter = $getter;
    }

    public static function cacheSetter(callable $setter)
    {
        static::$cachesetter = $setter;
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

    public static function cacheSet($table, array $fields)
    {
        if(static::runsInLaravel()) {
            return cache()->set('translatable' . $table, $fields);
        }

        if(!static::$cacheSetter) {
            throw new Exception('Cache not available. Declare a $translatable property on your model manually.');
        }

        return call_user_func_array(static::$cacheSetter, [$table, $fields]);
    }

    public static function cacheGet($table)
    {
        if(static::runsInLaravel()) {
            return cache()->get('translatable' . $table);
        }

        if(!static::$cacheGetter) {
            throw new Exception('Cache not available. Declare a $translatable property on your model manually.');
        }

        return call_user_func_array(static::$cacheSetter, [$table]);
    }

    protected static function runsInLaravel()
    {
        return class_exists('\Illuminate\Foundation\Application');
    }
}