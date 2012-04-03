<?php

namespace OperaCore;

require_once VENDOR_PATH . 'facebook/php-sdk/src/base_facebook.php';

/**
 * Extends the BaseFacebook class with the intent of using
 * PHP sessions to store user ids and access tokens.
 */
class Facebook extends \BaseFacebook
{

	/**
	 * @var \OperaCore\Session
	 */
	protected $session;

	/**
	 * @var array Supported keys
	 */
	protected static $kSupportedKeys = array( 'state', 'code', 'access_token', 'user_id' );

	/**
	 * Identical to the parent constructor, except that
	 * we start a PHP session to store the user ID and
	 * access token if during the course of execution
	 * we discover them.
	 *
	 * @param Array              $config the application configuration.
	 * @param \OperaCore\Session Session object
	 *
	 * @see BaseFacebook::__construct in facebook.php
	 */
	public function __construct( $config )
	{
		if ( isset( $config['session_container'] ) )
		{
			$this->session = $config['session_container'];
		}
		else
		{
			$this->session =& $_SESSION;
		}
		parent::__construct( $config );
	}

	protected function setPersistentData( $key, $value )
	{
		if ( !in_array( $key, self::$kSupportedKeys ) )
		{
			self::errorLog( 'Unsupported key passed to setPersistentData.' );
			return;
		}

		$session_var_name                 = $this->constructSessionVariableName( $key );
		$this->session[$session_var_name] = $value;
	}

	protected function getPersistentData( $key, $default = false )
	{
		if ( !in_array( $key, self::$kSupportedKeys ) )
		{
			self::errorLog( 'Unsupported key passed to getPersistentData.' );
			return $default;
		}

		$session_var_name = $this->constructSessionVariableName( $key );
		return isset( $this->session[$session_var_name] ) ? $this->session[$session_var_name] : $default;
	}

	protected function clearPersistentData( $key )
	{
		if ( !in_array( $key, self::$kSupportedKeys ) )
		{
			self::errorLog( 'Unsupported key passed to clearPersistentData.' );
			return;
		}

		$session_var_name = $this->constructSessionVariableName( $key );
		unset( $this->session[$session_var_name] );
	}

	protected function clearAllPersistentData()
	{
		foreach ( self::$kSupportedKeys as $key )
		{
			$this->clearPersistentData( $key );
		}
	}

	protected function constructSessionVariableName( $key )
	{
		return implode( '_', array( 'fb', $this->getAppId(), $key ) );
	}
}
