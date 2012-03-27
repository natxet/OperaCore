<?php

namespace OperaCore;

class Container extends \Pimple
{
	public function __construct( array $values = array() )
	{
		parent::__construct( $values );

		$this['Request'] = $this->share(
			function ()
			{
				return \Symfony\Component\HttpFoundation\Request::createFromGlobals();
			}
		);

		$this['Response'] =
			function ()
			{
				$response = new \Symfony\Component\HttpFoundation\Response();
				return $response;
			}
		;
	}

	public function init()
	{
		$this['config_path'] = APP_PATH . Bootstrap::CONFIG_REL_PATH;
		$this['config_env']  = ENV;
		$this['Config']      = $this->share(
			function ( $this )
			{
				return new Config( array( 'main', 'database', 'routes', 'gen/assets.gen' ), $this['config_env'], $this['config_path'] );
			}
		);

		$this['Router'] = $this->share(
			function ( $this )
			{
				return new Router( $this, $this['Config']->get( 'routes' ) );
			}
		);

		$assets = array();
		foreach ( $this['Config']->get( 'gen/assets.gen' ) as $k => $v )
		{
			$assets[$k] = $v['url'];
		}
		$this['template_params'] = array(
			'paths'       => array( APP_PATH . Bootstrap::VIEW_REL_PATH, OPERACORE_PATH . Bootstrap::VIEW_REL_PATH ),
			'cache'       => DEBUG ? false : APP_PATH . Bootstrap::CACHE_REL_PATH,
			'debug'       => DEBUG,
			'auto_reload' => true,
			'public_paths'=> $this['Config']->get( 'main', 'paths' ),
			'assets'      => $assets,
			'request_uri' => $this['Request']->getRequestUri()
		);
		$this['Template']        = $this->share(
			function ( $this )
			{
				return new Template( $this );
			}
		);

		$this['Database'] = $this->share(
			function ( $this )
			{
				return new Database( $this );
			}
		);

		$this['i18n_path'] = APP_PATH . Bootstrap::LOCALE_REL_PATH;
		$this['I18n']      = $this->share(
			function ( $this )
			{
				return new I18n( $this );
			}
		);

		$this['GeoIP'] = $this->share(
			function ( $this )
			{
				return new Model\GeoIP( $this );
			}
		);

		$this['smtp_gateways']  = array(
			'sendgrid' => $this['Config']->get( 'main', 'smtp_sendgrid' ),
			'gmail'    => $this['Config']->get( 'main', 'smtp_gmail' )
		);
		$this['email_defaults'] = $this['Config']->get( 'main', 'email' );
		$this['Mailer']         = $this->share(
			function ( $this )
			{
				return new Mailer( $this );
			}
		);

		$this['cookies_lifetime'] = $this['Config']->get( 'main', 'app', 'cookies_lifetime' );
		$this['cookies_domain'] = $this['Config']->get( 'main', 'paths', 'cookies_domain' );
		$this['Session'] =
			function( $this )
			{
				return new Session( $this['cookies_lifetime'], $this['cookies_domain'] );
			}
		;
	}
}
