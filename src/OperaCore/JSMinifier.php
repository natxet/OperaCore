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
		//require_once( __DIR__ . '/../natxet/CssMin/CssMin.php' );
		return \JShrink::minify( $string );
	}
}
