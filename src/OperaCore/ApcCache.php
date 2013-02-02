<?php

namespace OperaCore;

class ApcCache implements Cache
{
	const TTL = 6000;

	static public function get( $key ) {

		ApcCache::check();
		$res = false;
		$value = apc_fetch( $key, $res );

		return ( $res ) ? $value : NULL;
	}

	static public function set( $key, $value ) {

		ApcCache::check();

		return apc_store( $key, $value, ApcCache::TTL);
	}

	static public function del( $key ) {

		ApcCache::check();
		$res = false;
		apc_fetch( $key, $res );

		return ( $res ) ? apc_delete( $key ) : true;
	}

	protected function check() {

		if( !extension_loaded('apc') ) {

			throw new \Exception('APC extension not loaded');
		}
	}
}
