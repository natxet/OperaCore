<?php

namespace OperaCore;

interface Cache
{
	static public function get( $key );
	static public function set( $key, $value );
	static public function del( $key );
}
