<?php

namespace OperaCore;
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
		$public_paths = $c['template_params']['public_paths'];
		if( isset( $public_paths['img_locale'] ) )
		{
			$public_paths['img_locale'] = str_replace( '%LANG%', LANG, $public_paths['img_locale'] );
		}
		$twig_loader = new \Twig_Loader_Filesystem( $c['template_params']['paths'] );
		parent::__construct( $twig_loader, $c['template_params'] );
		$this->addGlobal( 'route', $c['Router'] );
		$this->addGlobal( 'path', $public_paths );
		$this->addGlobal( 'asset', $c['template_params']['assets'] );
		$this->addGlobal( 'request_uri', $c['template_params']['request_uri'] );
		$this->addGlobal( 'helper', new Helper() );
		$this->addExtension( new \Twig_Extensions_Extension_I18n() );

		$this->addExtension(new \natxet\NatxetTwigExtensions\Twig\Extension\PHPFunctionsExtension());
		$parser = new \dflydev\markdown\MarkdownParser();
		$this->addExtension(new \Misd\TwigMarkdowner\Twig\Extension\MarkdownerExtension($parser));

		if ( DEBUG ) $this->addExtension( new \Twig_Extensions_Extension_Debug() );
	}
}
