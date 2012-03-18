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
			list( $controller, $action, $params ) = $c['Router']->getRoute();

			$class_name = '\\' . APP . '\\Controller\\' . $controller;
			$controller = new $class_name( $c );

			Profile::Checkpoint( 'Routing and Controller Construction' );

			$method = "action$action";
			$controller->$method( $params );
		}
		catch( \OperaCore\Exception\PageNotFound $e )
		{
			$class_name = '\\' . APP . '\\Controller\\' . 'Error';
			$controller = new $class_name( $c );
			$controller->action404( array() );
		}
		catch( \Exception $e )
		{
			if ( PROFILE ) Profile::Collect(
				'Exception', array(
				                  "message"   => $e->getMessage(),
				                  'trace'    => $e->getTraceAsString()
				             )
			);

			error_log( $e->getMessage() );

			$class_name = '\\' . APP . '\\Controller\\' . 'Error';
			$controller = new $class_name( $c );
			$controller->action500( array('message' => $e->getMessage() ) );

		}

		Profile::Checkpoint( 'Controller action executed. End of OperaCore' );

		if( DEBUG && PROFILE )
		{
			$profile = new Module\Profile( $c );
			$profile->actionShow( array() );
		}
	}
}
