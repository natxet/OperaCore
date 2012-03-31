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

	/**
	 * @var array the params for the action
	 */
	public $params = array();

	/**
	 * @var Session Response object
	 */
	protected $session;


	/**
	 * @var bool just for the case we print a redirection template
	 */
	protected $this_is_a_redirection = false;

	public function __construct( $container )
	{
		$this->container   = $container;
		$this->template    = $container['Template'];
		$this->request_uri = $container['Request']->getRequestUri();
		$this->response    = $container['Response'];
		$charset = $this->container['Config']->get( 'main', 'app', 'encoding' );
		$this->response->setCharset( $charset );
		$this->response->setPublic();
	}

	public function action( $action, $params )
	{
		$this->params = $params;
		$method = "action$action";
		return $this->$method( $params ); // dejar de enviar params
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
		if ( PROFILE ) $this->renderPreProfile( $template );

		$content = $this->renderTemplate( $template );

		return ( $print ) ? $this->renderResponse( $content ) : $content;
	}

	protected function renderResponse( $content ) {

		$this->response->setContent( $content );
		// TODO: $response->prepare($request); ?
		// die( $this->response->__toString());
		$res = $this->response->send();

		if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Printed' );

		return $res;
	}

	protected function renderTemplate( $template )
	{
		$session_message = $this->getSessionMessage();
		if( $session_message)
		{
			$this->addMessage( $session_message['message'], $session_message['type'] );
		}

		$this->template->addGlobal( 'messages', $this->messages );
		$content = $this->template->render( $template, $this->context );

		if ( PROFILE ) Profile::Checkpoint( 'Controller - Template Rendered' );

		return $content;
	}

	protected function renderPreProfile( $template )
	{
		Profile::Checkpoint( 'Controller - Action executed: starting render' );

		Profile::Collect(
			'Templates', array(
			                  "template"   => $template,
			                  'context'    => $this->context
			             )
		);
	}

	protected function response()
	{
		$this->response->send();
	}

	protected function renderJson( $js_var = false )
	{
		$this->response->headers->set( 'Content-Type', 'application/json', true);

		$allow_origin = $this->container['Config']->get( 'main', 'paths', 'allow_origin' );
		$this->response->headers->set( 'Access-Control-Allow-Origin', $allow_origin);
		$this->response->headers->set( 'Access-Control-Allow-Credentials', 'true');

		$content = ( $js_var ) ? "var $js_var = " : '';
		$content .= json_encode( $this->context );

		switch ( json_last_error() )
		{

			case JSON_ERROR_NONE:
				break;
			case JSON_ERROR_DEPTH:
				$content .= ' - Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$content .= ' - Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$content .= ' - Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$content .= ' - Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$content .= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$content .= ' - Unknown error';
				break;
		}

		$this->response->setContent( $content );
		$this->response->send();
	}

	protected function redirect( $destination, $permanent = true )
	{
		$status = $permanent ? 301 : 307;

		$this->this_is_a_redirection = true;

		if ( DEBUG )
		{
			$this->context = array(
				'seconds'     => 60,
				'status'      => $status,
				'destination' => $destination
			);
			$this->render( 'redirectionDebug.html.twig' );
			var_dump($_SESSION);
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

	public function setSessionMessage( $message, $type = self::TEMPLATE_MESSAGE_INFO )
	{
		$this->setSessionVar( 'session_message', array( 'type' => $type, 'message' => $message) );
	}

	public function getSessionMessage( )
	{
		$session_message = $this->getSessionVar( 'session_message' );

		if( !$this->this_is_a_redirection )
		{
			$this->unsetSessionVar( 'session_message' );
		}

		return $session_message;
	}

	public function getSessionVar( $key )
	{
		if( !isset( $this->session ) ) $this->session = $this->container['Session'];
		return isset( $this->session[$key] ) ? $this->session[$key] : NULL;
	}

	public function setSessionVar( $key, $value )
	{
		if( !isset( $this->session ) ) $this->session = $this->container['Session'];
		$this->session[$key] = $value;
	}

	public function unsetSessionVar( $key )
	{
		if( !isset( $this->session ) ) $this->session = $this->container['Session'];
		if( isset( $this->session[$key] ) ) unset( $this->session[$key] );
	}

	protected function getTransVar( $trans, $vars )
	{
		return str_replace( array_keys($vars), array_values($vars), $trans );
	}
}
