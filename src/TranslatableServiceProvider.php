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
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'translatable.php', 'translatable');

        TranslatableConfig::cacheGetter(function($table) {
            return $this->app['cache']->get('translatable.' . $table);
        });

        TranslatableConfig::cacheSetter(function($table, $fields) {
            return $this->app['cache']->set('translatable.' . $table, $fields);
        });

        TranslatableConfig::currentLocaleGetter(function() {
            return $this->app->getLocale();
        });

        TranslatableConfig::fallbackLocaleGetter(function() {
            return $this->app->getFallbackLocale();
        });

        TranslatableConfig::setDbSettings(
            $this->app['config']->get('translatable.db_settings')
        );

        TranslatableConfig::setDefaults(
            $this->app['config']->get('translatable.defaults')
        );
    }
}