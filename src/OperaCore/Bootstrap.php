<?php

namespace OperaCore;

// TO-DO: when all projects are migrated, erase the "defined"
defined( 'OPERACORE_PATH' )   or define( 'OPERACORE_PATH', __DIR__ . '/' );
defined( 'VENDOR_PATH' )      or define( 'VENDOR_PATH', realpath( OPERACORE_PATH . '../../../../' ) . '/' );
defined( 'BASE_PATH' )        or define( 'BASE_PATH', dirname( VENDOR_PATH ) . '/' );
defined( 'APPS_PATH' )        or define( 'APPS_PATH', BASE_PATH . 'app/' );

require( 'autoload.php' );

Profile::Checkpoint( 'Begin of OperaCore' );

class Bootstrap
{
	// Paths
	const LOCALE_REL_PATH      = '/Locale';
	const CONFIG_REL_PATH      = '/Config';
	const VIEW_REL_PATH        = '/View';
	const CACHE_REL_PATH       = '/tmp/Cache';
	// Environments
	const DEFAULT_ENV    = 'pro';
	const PRODUCTION_ENV = 'pro'; // production environment
	const DEVELOPMENT_ENV = 'dev'; // development environment

	/**
	 * @var String name of the app
	 */
	public $app;

	/**
	 * @var Container
	 */
	protected $container;

	public function __construct( $app )
	{
		$this->app       = $app;
		$this->container = new Container();
		$this->defineEnv( $this->container );
		$this->defineApp();
		$this->container->init();

		Profile::Checkpoint( 'Container has been Created' );
	}

	/**
	 * @return string the env (dev, pro)
	 *
	 * @param $c Container
	 */
	protected function getEnv( $c )
	{
		$env_filename = BASE_PATH . '.env';
		if (file_exists( $env_filename )) {
			return trim( file_get_contents( $env_filename ) );
		} elseif ($this->isCli()) {
			global $argv;
			return $argv[2];
		} else {
			return $c['Request']->server->get( 'APP_ENV' );
		}
	}

	/**
	 * @return string the App f.i. AcmeApp
	 *
	 */
	protected function getApp()
	{
		return $this->app;
	}

	/**
	 * Defines the ENV and DEBUG environment globals
	 *
	 * @param $c Container
	 */
	protected function defineEnv( $c )
	{
		$env = $this->getEnv( $c );
		if ( empty( $env ) ) $env = self::DEFAULT_ENV;
		define( 'ENV', $env );
		define( 'DEBUG', ( self::DEVELOPMENT_ENV == $env ) );
	}

	/**
	 * Defines the ENV and DEBUG environment globals
	 *
	 * @throws \UnexpectedValueException
	 */
	protected function defineApp()
	{
		$app = $this->getApp();
		define( 'APP', $app );
		define( 'APP_PATH', APPS_PATH . "$app" );
		if( !is_dir( APP_PATH ) ) {
			throw new \UnexpectedValueException( "Could not find the app $app at " . APP_PATH );
		}
	}

	/**
	 * @return Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @return bool returns true if PHP is executed from command line
	 */
	public function isCli()
	{
		return php_sapi_name() === "cli";
	}

}
