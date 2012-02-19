<?php

namespace natxet\OperaCore;

class Dispatcher
{
	/**
	 * @param $c Container The container class
	 */
	public function __construct( $c )
	{

		list( $controller, $action, $params ) = $c['Router']->getRoute();

		$class_name = '\\' . APP . '\\Controller\\' . $controller;
		$controller = new $class_name( $c );

		Profile::Checkpoint( 'Routing and Controller Construction' );

		$method = "action$action";
		$controller->$method( $params );

		Profile::Checkpoint( 'Controller action executed. End of OperaCore' );

		if( DEBUG && PROFILE )
		{
			$profile = new Module\Profile( $c );
			$profile->run( 'Show', array() );
		}
	}
}
