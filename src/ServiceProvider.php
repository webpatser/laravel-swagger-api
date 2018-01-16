<?php

namespace LaravelApi;


use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;


class ServiceProvider extends IlluminateServiceProvider
{
	
	
	public function register ()
	{
		$this->mergeConfigFrom ( __DIR__ . '/../resources/config.php', 'api' );
		
		$this->app->singleton ( Api::class );
		
		if ( $this->app->runningInConsole () )
		{
			$this->commands (
				Console\ApiCacheCommand::class,
				Console\ApiClearCommand::class
			);
		}
	}
	
	
	public function boot ( Registrar $router )
	{
		$this->initRoutes ( $router );
		
		$resourcesPath = __DIR__ . '/../resources';
		
		$this->loadViewsFrom ( "$resourcesPath/views", 'api' );
		
		$this->publishes ( [ "$resourcesPath/config.php" => config_path ( 'api.php' ) ], 'config' );
		
		$swaggerPath = base_path ( 'vendor/swagger-api/swagger-ui/dist/' );
		$this->publishes ( [ $swaggerPath => public_path ( 'vendor/swagger-ui' ) ], 'public' );
	}
	
	
	protected function initRoutes ( Registrar $router )
	{
		$router->prefix ( config ( 'api.prefix' ) )
			   ->middleware ( 'api' )
			   ->namespace ( 'LaravelApi\\Http\\Controllers' )
			   ->group ( function ( Registrar $router ) {
			
				   if ( $jsonPath = config ( 'api.swagger_json_path' ) )
				   {
					   $router->get ( $jsonPath, 'DocsController@json' )
							  ->name ( 'api.swagger' );
				
					   if ( $uiPath = config ( 'api.swagger_ui_path' ) )
					   {
						   $router->get ( $uiPath, 'DocsController@index' )
								  ->name ( 'api.docs' );
					   }
				   }
			
			   } );
	}
	
	
	public function provides ()
	{
		return [ Api::class ];
	}
	
}


