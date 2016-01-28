<?php namespace Laraplus\Data;

use Exception;

class TranslatableConfig
{
    protected static $config = [
        'locale' => [
            'current_getter' => null,
            'fallback_getter' => null,
        ],
        'cache' => [
            'getter' => null,
            'setter' => null
        ],
        'db_settings' => [
            'table_suffix' => '_i18n',
            'locale_field' => 'locale'
        ],
        'defaults' => [
            'only_translated' => false,
            'enable_fallback' => true,
        ]
    ];

    public static function currentLocaleGetter(callable $current)
    {
        static::$config['locale']['current_getter'] = $current;
    }

    public static function fallbackLocaleGetter(callable $fallback)
    {
        static::$config['locale']['fallback_getter'] = $fallback;
    }

    public static function cacheGetter(callable $getter)
    {
        static::$config['cache']['getter'] = $getter;
    }

    public static function cacheSetter(callable $setter)
    {
        static::$config['cache']['setter'] = $setter;
    }

    public static function setDbSettings(array $settings)
    {
        static::$config['db_settings'] = array_merge(static::$config['db_settings'], $settings);
    }

    public static function setDefaults(array $defaults)
    {
        static::$config['defaults'] = array_merge(static::$config['defaults'], $defaults);
    }

    public static function currentLocale()
    {
        static::checkIfSet('locale', 'current_getter');

        return call_user_func(static::$config['locale']['current_getter']);
    }

    public static function fallbackLocale()
    {
        static::checkIfSet('locale', 'fallback_getter');

        return call_user_func(static::$config['locale']['fallback_getter']);
    }

    public static function onlyTranslated()
    {
        return static::$config['defaults']['only_translated'];
    }

    public static function withFallback()
    {
        return static::$config['defaults']['enable_fallback'];
    }

    public static function dbSuffix()
    {
        return static::$config['db_settings']['table_suffix'];
    }

    public static function dbKey()
    {
        return static::$config['db_settings']['locale_field'];
    }

    public static function cacheSet($table, array $fields)
    {
        static::checkIfSet('cache', 'setter');

        return call_user_func_array(static::$config['cache']['setter'], [$table, $fields]);
    }

    public static function cacheGet($table)
    {
        static::checkIfSet('cache', 'getter');

        return call_user_func_array(static::$config['cache']['getter'], [$table]);
    }

    protected function checkIfSet($key1, $key2)
    {
        if(empty(static::$config[$key1][$key2])) {
            throw new Exception("Translatable is not configured correctly. Config for [$key1.$key2] is missing.");
        }
    }
}