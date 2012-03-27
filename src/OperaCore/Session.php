<?php

namespace OperaCore;

class Session extends \ArrayObject
{
	public function __construct( $cookies_lifetime = 3600, $cookies_domain = '' )
	{
		session_set_cookie_params( $cookies_lifetime, '/', $cookies_domain );
		if ( !session_id() ) session_start();
		parent::__construct( (array) $_SESSION );
	}

	public function sync()
	{
		$_SESSION = $this->getArrayCopy();
	}

	public function offsetSet( $index, $newval )
	{
		parent::offsetSet( $index, $newval );
		$this->sync();
	}

	public function offsetUnset( $index )
	{
		parent::offsetUnset( $index );
		$this->sync();
	}
}
