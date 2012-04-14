<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 19/02/12
 */
class CssMinifier implements Minifier
{
	static public function minify( $string, array $params = null)
	{
		require_once( VENDOR_PATH . 'natxet/CssMin/src/CssMin.php' );
		return \CssMin::minify( $string );
	}
}
