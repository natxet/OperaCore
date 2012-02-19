<?php

namespace natxet\OperaCore;

abstract class Controller
{
	/**
	 * @var Container Container
	 */
	protected $container;

	/**
	 * @var Template Template
	 */
	protected $template;

	public function __construct( $container )
	{
		$this->container = $container;
		$this->template  = $container['Template'];
	}

	protected function getModel( $model )
	{
		$class_name = '\\' . APP . '\\Model\\' . $model;
		return new $class_name( $this->container );
	}

	protected function render( $template, $context )
	{
		if ( PROFILE ) Profile::Checkpoint( 'Controller - Action executed: starting render' );

		if ( PROFILE ) Profile::Collect(
			'Templates', array(
			                  "template"   => $template,
			                  'context'    => $context
			             )
		);

		echo $this->template->render( $template, $context );

		if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Rendered' );
	}

	protected function renderJson( $var, $js_var = false )
	{
		header( 'Content-Type: application/json; charset=utf-8', true, 200 );
		if ( $js_var ) echo "var $js_var = ";
		echo json_encode( $var );

		switch ( json_last_error() )
		{

			case JSON_ERROR_NONE:
				break;
			case JSON_ERROR_DEPTH:
				echo ' - Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				echo ' - Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				echo ' - Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				echo ' - Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				echo ' - Unknown error';
				break;
		}
	}

	protected function redirect( $destination, $permanent = true )
	{
		$status = $permanent ? "301 Moved Permanently" : "307 Moved Temporarely";

		if ( DEBUG )
		{
			print( '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
					</head>
					<body>
					<H1>DEBUG: this is not for production</H1>' );
			print( "HTTP/1.0 $status" );
			print( "<br/>" );
			print( "Location: <a href=\"$destination\">$destination</a>" );
			print( '</body></html>' );
		}
		else
		{
			header( "HTTP/1.0 $status" );
			header( "Location: $destination" );
		}

		die();
	}
}
