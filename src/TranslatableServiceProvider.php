<?php namespace Laraplus\Data;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        TranslatableConfig::cacheGetter(function($table) {
            return $this->app['cache']->get('translatable.' . $table);
        });

        TranslatableConfig::cacheSetter(function($table, $fields) {
            return $this->app['cache']->forever('translatable.' . $table, $fields);
        });

        TranslatableConfig::currentLocaleGetter(function() {
            return $this->app->getLocale();
        });

        TranslatableConfig::fallbackLocaleGetter(function() {
            return method_exists($this->app, 'getFallbackLocale')
                ? $this->app->getFallbackLocale()
                : config('app.fallback_locale');
        });
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $config = dirname(__DIR__) . '/config/translatable.php';

        $this->mergeConfigFrom($config, 'translatable');
        $this->publishes([$config => config_path('translatable.php')], 'config');

        TranslatableConfig::setDbSettings(
            $this->app['config']->get('translatable.db_settings')
        );

        TranslatableConfig::setDefaults(
            $this->app['config']->get('translatable.defaults')
        );
    }
}