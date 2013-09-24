<?php

namespace OperaCore;

class Dispatcher
{
	/**
	 * @param $c Container The container class
	 */
	public function __construct( $c )
	{
		try
		{
			list( $controller, $action, $params, $route_key ) = $c['Router']->getRoute();

			$controller = $this->getController( $controller, $c );
			Profile::Checkpoint( 'Routing and Controller Construction' );
			$controller->action( $action, $params, $route_key );
		}
		catch( \OperaCore\Exception\PageNotFound $e )
		{
			$controller = $this->getController( 'Error', $c );
			Profile::Checkpoint( 'Preparing 404 Controller' );
			$controller->action404();
		}
        catch( \OperaCore\Exception\Forbidden $e )
        {
            $controller = $this->getController( 'Error', $c );
            Profile::Checkpoint( 'Preparing 403 Controller' );
            $controller->addMessage($e->getMessage(), Controller::TEMPLATE_MESSAGE_ERROR);
            $controller->action403();
        }
		catch( \Exception $e )
		{
			error_log( "UNCAUGHT EXCEPTION IN OPERACORE: " . $e->getMessage() . "\n >>> " . $e->getTraceAsString() );

			if ( defined( 'PROFILE' ) && PROFILE ) Profile::Collect(
				'Exception', array(
				                  "message"   => $e->getMessage(),
				                  'trace'    => $e->getTraceAsString()
				             )
			);

			if( !defined( 'PROFILE' ) ) die( $e->getMessage() );

			$class_name = '\\' . APP . '\\Controller\\' . 'Error';
			$controller = new $class_name( $c );
			$controller->action500( $e->getMessage() );

		}

		Profile::Checkpoint( 'Controller action executed. End of OperaCore' );

		if( defined( 'DEBUG' ) && DEBUG && defined( 'PROFILE' ) && PROFILE )
		{
			$profile = new Module\Profile( $c );
			$profile->actionShow( array() );
		}
	}

	/**
	 * @param $controller string Name of the controller
	 * @param $container Container
	 * @return Controller
	 */
	protected function getController( $controller, $container )
	{
		$class_name = '\\' . APP . '\\Controller\\' . $controller;

		return new $class_name( $container );
	}
}
