<?php

namespace OperaCore;

use \dflydev\markdown\MarkdownParser;

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
	 * @var string the code of the called route
	 */
	protected $route_key;

	/**
	 * @var bool just for the case we print a redirection template
	 */
	protected $this_is_a_redirection = false;

	protected $profile = false;

	public function __construct( $container )
	{
		$this->setContainer( $container );
		$this->request_uri = $container['Request']->getRequestUri();
		$charset           = $this->container['Config']->get( 'main', 'app', 'encoding' );
		$this->getResponse()->setCharset( $charset );
		$this->getResponse()->setPublic();
		$this->profile = ( defined( 'PROFILE' ) && PROFILE );
		// Use config > main > app > trust_proxy_headers como 1 o 0 for Request to trustProxyData
		// Even though we put "true", the config returns "1" as a string
		$proxies = $this->container['Config']->get( 'main', 'app', 'proxies' );
		if ( is_array( $proxies ) && count( $proxies ) ) {
            $this->container['Request']->setTrustedProxies( $proxies );
		}
	}

	/**
	 * @param $name      string
	 * @param $arguments array
	 *
	 * @throws \Exception
	 */
	public function __call( $name, $arguments )
	{
		if (!method_exists( $this, $name )) {
			throw new \Exception( get_class( $this ) . "->$name() does not exist" );
		}
	}

	/**
	 * @param $action    string
	 * @param $params    array
	 * @param $route_key string
	 */
	public function action( $action, $params = array(), $route_key = '' )
	{
        try {
            $method          = "action$action";
            $this->route_key = $route_key;
            $this->params    = $params;
            $this->$method( $params );
        }
        catch( Exception $e ) {
            var_dump($e->getTraceAsString());die;
        }
	}

	/**
	 * @param $model string The model name.
     * @param $app string The string of the app, if you want to load another apps model. Default: current APP
	 *
	 * @return Model A model class
     * @throws \Exception
	 */
	protected function getModel( $model, $app = APP )
	{
        $class_name = '\\' . $app . '\\Model\\' . $model;
        if(!class_exists( $class_name)) {
            throw new \Exception( "Class $class_name does not exist" );
        }
		return new $class_name( $this->container );
	}

	protected function getPath( $route_key, $params = array(), $absolute = true )
	{
        // TO-DO: si estamos con profiler activado, mostrar el path con profiler
		return $this->container['Router']->getPath( $route_key, $params, $absolute );
	}

    public function addContext( $key, $value)
    {
        $this->context[$key] = $value;
    }

    public function parseMarkdownTemplate( $template_name, array $context = array() )
    {
        $markdown_filename = APP_PATH . Bootstrap::VIEW_REL_PATH . '/' . $template_name;
        return $this->parseMarkdownFile( $markdown_filename, $context );
    }

    public function parseMarkdownFile( $markdown_filename, array $context = array() )
    {
        if(!is_readable($markdown_filename)) throw new Exception\PageNotFound();
        return $this->parseMarkdown( file_get_contents( $markdown_filename ), $context );
    }

    public function parseMarkdown( $markdown, array $context = array() )
    {
        $parser = new MarkdownParser();
        $rendered = $this->renderString($markdown, $context);
        return $parser->transformMarkdown( $rendered );

    }

    public function renderString($string, array $context = array())
    {
        $template = new Template($this->container, new \Twig_Loader_String());
        return $template->render($string, $context);
    }

	protected function render( $template, $print = true )
	{
		if ($this->profile) {
			$this->renderPreProfile( $template );
		}

		$content = $this->renderTemplate( $template );

		return ( $print ) ? $this->renderResponse( $content ) : $content;
	}

	protected function renderResponse( $content )
	{

		$this->getResponse()->setContent( $content );
		// TODO: $response->prepare($request); ?
		// die( $this->getResponse()->__toString());
		$res = $this->getResponse()->send();

		if ($this->profile) {
			Profile::Checkpoint( 'Controller - Template Printed' );
		}

		return $res;
	}

	protected function renderTemplate( $template )
	{
		$this->getTemplate()->addGlobal( 'messages', $this->messages );
		$content = $this->getTemplate()->render( $template, $this->context );

		if ($this->profile) Profile::Checkpoint( 'Controller - Template Rendered' );

		return $content;
	}

	protected function renderPreProfile( $template )
	{
		Profile::Checkpoint( 'Controller - Action executed: starting render' );

		Profile::Collect(
			'Templates',
			array(
			     "template" => $template,
			     'context'  => $this->context
			)
		);
	}

	protected function response()
	{
		$this->getResponse()->send();
	}

	protected function renderJson( $js_var = false )
	{
		$this->getResponse()->headers->set( 'Content-Type', 'application/json', true );

		$allow_origin = $this->container['Config']->get( 'main', 'paths', 'allow_origin' );
		$this->getResponse()->headers->set( 'Access-Control-Allow-Origin', $allow_origin );
		$this->getResponse()->headers->set( 'Access-Control-Allow-Credentials', 'true' );

		$content = ( $js_var ) ? "var $js_var = " : '';
		$content .= json_encode( $this->context );

		switch (json_last_error()) {

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

		$this->getResponse()->setContent( $content );
		$this->getResponse()->send();
	}

	protected function redirect( $destination, $permanent = true )
	{
		$status = $permanent ? 301 : 307;

		$this->this_is_a_redirection = true;

		if (DEBUG) {
			$this->context = array(
				'seconds'     => 60,
				'status'      => $status,
				'destination' => $destination
			);
			$this->render( 'RedirectionDebug.twig' );
			if (isset( $_SESSION )) var_dump( $_SESSION );
		} else {
			$this->setResponse( new \Symfony\Component\HttpFoundation\RedirectResponse( $destination, $status ) );
			$this->getResponse()->send();
		}

		die();
	}

	protected function paginator( $base_url, $total_rows, $current_page, $results_per_page, $num_pages = null )
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
		$this->setSessionVar( 'session_message', array( 'type' => $type, 'message' => $message ) );
	}

	public function getSessionMessage()
	{
		$session_message = $this->getSessionVar( 'session_message' );

		if (!$this->this_is_a_redirection) {
			$this->unsetSessionVar( 'session_message' );
		}

		return $session_message;
	}

	public function getSessionVar( $key )
	{
		if (!isset( $this->session )) $this->session = $this->container['Session'];
		return isset( $this->session[$key] ) ? $this->session[$key] : null;
	}

	public function setSessionVar( $key, $value )
	{
		if (!isset( $this->session )) $this->session = $this->container['Session'];
		$this->session[$key] = $value;
	}

	public function unsetSessionVar( $key )
	{
		if (!isset( $this->session )) $this->session = $this->container['Session'];
		if (isset( $this->session[$key] )) unset( $this->session[$key] );
	}

	protected function getTransVar( $trans, $vars )
	{
		return str_replace( array_keys( $vars ), array_values( $vars ), gettext( $trans ) );
	}

	protected function getParam( $key, $type = null )
	{
		$param = isset( $this->params[$key] ) ? $this->params[$key] : null;
		switch ($type) {
			case 'int':
				$param = (int) $param;
		}
		return $param;
	}

    protected function getAllQuery() {
        return $this->container['Request']->query->all();
    }

    protected function getAllRequest() {
        return $this->container['Request']->request->all();
    }

    protected function getQuery( $key ) {
        return $this->container['Request']->query->get( $key );
    }

    protected function getRequest( $key ) {
        return $this->container['Request']->request->get( $key );
    }

	/**
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 */
	public function setResponse( $response )
	{
		$this->response = $response;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse()
	{
		if (!isset( $this->response )) {
			$this->setResponse( $this->container['Response'] );
		}

		return $this->response;
	}

	/**
	 * @param \OperaCore\Container $container
	 */
	public function setContainer( $container )
	{
		$this->container = $container;
	}

	/**
	 * @return \OperaCore\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @param \OperaCore\Template $template
	 */
	public function setTemplate( $template )
	{
		$this->template = $template;
	}

	/**
	 * @return \OperaCore\Template
	 */
	public function getTemplate()
	{
		if (!isset( $this->template )) {
			$this->setTemplate( $this->container['Template'] );
		}
		return $this->template;
	}
}
