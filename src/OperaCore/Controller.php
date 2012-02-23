<?php

namespace OperaCore;

abstract class Controller
{
	const TEMPLATE_RENDER_RETURN = 0;
	const TEMPLATE_RENDER_PRINT  = 1;
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
		$this->container   = $container;
		$this->template    = $container['Template'];
		$this->request_uri = $container['Request']->getRequestUri();
	}

	protected function getModel( $model )
	{
		$class_name = '\\' . APP . '\\Model\\' . $model;
		return new $class_name( $this->container );
	}

	protected function render( $template, $context, $print = true )
	{
		if ( PROFILE ) Profile::Checkpoint( 'Controller - Action executed: starting render' );

		if ( PROFILE ) Profile::Collect(
			'Templates', array(
			                  "template"   => $template,
			                  'context'    => $context
			             )
		);

		$output = $this->template->render( $template, $context );

		if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Rendered' );

		if ( $print )
		{
			echo $output;
			if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Printed' );
		}
		else return $output;

		return true;
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
			$context = array(
				'seconds'     => 10,
				'status'      => $status,
				'destination' => $destination
			);
			$this->render( 'redirectionDebug.html.twig', $context );
		}
		else
		{
			header( "HTTP/1.0 $status" );
			header( "Location: $destination" );
		}

		die();
	}

	protected function paginator( $base_url, $total_rows, $current_page, $results_per_page )
	{
		$paginator = new \OperaCore\Module\Paginator( $this->container );

		return $paginator->getHtml( $base_url, $total_rows, $current_page, $results_per_page );
	}
}
