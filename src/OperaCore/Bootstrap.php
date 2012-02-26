<?php

namespace OperaCore;

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
	 * @var Container
	 */
	protected $container;

	public function __construct()
	{
		$this->container = new Container();

		$this->defineEnv( $this->container );
		$this->defineApp( $this->container );

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
		if( isCli() )
		{
			global $argv;
			return $argv[2];
		}
		else
		{
			return $c['Request']->server->get( 'APP_ENV' );
		}
	}

	/**
	 * @return string the App f.i. AcmeApp
	 *
	 * @param $c Container
	 */
	protected function getApp( $c )
	{
		if( isCli() )
		{
			global $argv;
			return $argv[3];
		}
		else
		{
			return $c['Request']->server->get( 'APP_APP' );
		}
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
	 * @param $c Container
	 */
	protected function defineApp( $c )
	{
		$app = $this->getApp( $c );
		define( 'APP', $app );
		define( 'APP_PATH', APPS_PATH . "/$app" );
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
