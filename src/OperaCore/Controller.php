<?php

namespace OperaCore;

abstract class Controller
{
	const TEMPLATE_RENDER_RETURN = 0;
	const TEMPLATE_RENDER_PRINT  = 1;

	const TEMPLATE_MESSAGE_INFO  = 'info';
	const TEMPLATE_MESSAGE_ALERT = 'alert';
	const TEMPLATE_MESSAGE_ERROR = 'error';

	/**
	 * @var Container Container
	 */
	protected $container;

	/**
	 * @var Template Template
	 */
	protected $template;

	/**
	 * @var array context for the template
	 */
	protected $context = array();

	/**
	 * @var \Symfony\Component\HttpFoundation\Response Response object
	 */
	protected $response;

	/**
	 * @var array messages to be sent to the user in the template
	 */
	protected $messages = array();

	public function __construct( $container )
	{
		$this->container   = $container;
		$this->template    = $container['Template'];
		$this->request_uri = $container['Request']->getRequestUri();
		$this->response    = $container['Response'];
	}

	protected function getModel( $model )
	{
		$class_name = '\\' . APP . '\\Model\\' . $model;
		return new $class_name( $this->container );
	}

	protected function getPath( $route_key, $params = array(), $absolute = true )
	{
		return $this->container['Router']->getPath( $route_key, $params, $absolute );
	}

	protected function render( $template, $print = true )
	{
		if ( PROFILE ) Profile::Checkpoint( 'Controller - Action executed: starting render' );

		if ( PROFILE ) Profile::Collect(
			'Templates', array(
			                  "template"   => $template,
			                  'context'    => $this->context
			             )
		);


		$this->template->addGlobal( 'messages', $this->messages );

		$output = $this->template->render( $template, $this->context );

		if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Rendered' );

		if ( $print )
		{
			$this->response->setContent( $output );
			// TODO: $response->prepare($request); ?
			// die( $this->response->__toString());
			$this->response->send();
			if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Printed' );
		}
		else return $output;

		return true;
	}

	protected function response()
	{
		$this->response->send();
	}

	protected function renderJson( $js_var = false )
	{
		header( 'Content-Type: application/json; charset=utf-8', true, 200 );

		if ( $js_var ) echo "var $js_var = ";
		echo json_encode( $this->context );

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
		$status = $permanent ? 301 : 307;

		if ( DEBUG )
		{
			$this->context = array(
				'seconds'     => 10,
				'status'      => $status,
				'destination' => $destination
			);
			$this->render( 'redirectionDebug.html.twig' );
		}
		else
		{
			$this->response = new \Symfony\Component\HttpFoundation\RedirectResponse( $destination, $status );
			$this->response->send();
		}

		die();
	}

	protected function paginator( $base_url, $total_rows, $current_page, $results_per_page, $num_pages = NULL )
	{
		$paginator = new \OperaCore\Module\Paginator( $this->container );

		return $paginator->getHtml( $base_url, $total_rows, $current_page, $results_per_page, $num_pages );
	}

	public function addMessage( $message, $type = self::TEMPLATE_MESSAGE_INFO )
	{
		$this->messages[$type][] = $message;
	}
}
