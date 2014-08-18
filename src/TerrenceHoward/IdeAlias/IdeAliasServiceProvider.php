<?php
/**
 * IdeAlias service provider. Used for defining IoC bindings, event
 * listeners, etc.
 *
 * @package IdeAlias
 * @license MIT
 */
namespace TerrenceHoward\IdeAlias;

use Illuminate\Support\ServiceProvider;

/**
 * IdeAliasServiceProvider
 *
 * @author Michael Funk <mike.funk@internetbrands.com>
 */
class IdeAliasServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // register command
        $this->app->bind(
            'ide.alias',
            'TerrenceHoward\IdeAlias\Commands\IdeAliasCommand'
        );
        $this->commands('ide.alias');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
