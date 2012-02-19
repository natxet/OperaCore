<?php

namespace natxet\OperaCore;
/**
 * User: nacho
 * Date: 03/02/12
 */
class Template extends \Twig_Environment
{
	/**
	 * @param $c Container
	 */
	public function __construct( $c )
	{
		$twig_loader = new \Twig_Loader_Filesystem( $c['template_params']['paths'] );
		parent::__construct( $twig_loader, $c['template_params'] );
		$this->addGlobal( 'route', $c['Router'] );
		$this->addGlobal( 'path', $c['template_params']['public_paths'] );
		$this->addGlobal( 'asset', $c['template_params']['assets'] );
		$this->addGlobal( 'helper', new Helper() );
		$this->addExtension( new \Twig_Extensions_Extension_I18n() );
		if ( DEBUG ) $this->addExtension( new \Twig_Extensions_Extension_Debug() );
	}
}
