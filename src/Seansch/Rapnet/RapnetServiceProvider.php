<?php namespace Seansch\Rapnet;

use Illuminate\Support\ServiceProvider;

class RapnetServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        $this->publishes([
            __DIR__ . '/../../config/rapnet.php' => config_path('rapnet.php'),
        ]);

	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('rapnet', function(){
            return new \Seansch\Rapnet\Rapnet();
        });
	}

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return array('Seansch\Rapnet\Rapnet');
    }
}
