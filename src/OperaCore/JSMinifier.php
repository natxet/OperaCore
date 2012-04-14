<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 19/02/12
 */
class JSMinifier implements Minifier
{
	static public function minify( $string, array $params = null)
	{
		require_once( VENDOR_PATH . 'natxet/JShrink/src/JShrink.php' );
		return \JShrink::minify( $string );
	}
}
