<?php

namespace OperaCore;

use natxet\NatxetTwigExtensions\Twig\Extension\PHPFunctionsExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
use Aptoma\Twig\Extension\MarkdownExtension;

/**
 * User: nacho
 * Date: 03/02/12
 */
class Template extends \Twig_Environment
{
    /**
     * @param Container $c
     * @param \Twig_LoaderInterface $twig_loader
     */
    public function __construct( Container $c, \Twig_LoaderInterface $twig_loader = null )
    {
        if (empty( $twig_loader )) {
            $twig_loader = new \Twig_Loader_Filesystem( $c['template_params']['paths'] );
        }
        parent::__construct( $twig_loader, $c['template_params'] );

        $this->setConfig($c);
    }

    /**
     * @param Container $c
     */
    public function setConfig( Container $c )
    {
        $public_paths = $c['template_params']['public_paths'];

        if (isset( $public_paths['img_locale'] )) {
            $public_paths['img_locale'] = str_replace( '%LANG%', LANG, $public_paths['img_locale'] );
        }
        $this->addGlobal( 'path', $public_paths );

        $this->addGlobal( 'route', $c['Router'] );
        $this->addGlobal( 'asset', $c['template_params']['assets'] );
        $this->addGlobal( 'request_uri', $c['template_params']['request_uri'] );
        $this->addGlobal( 'helper', new Helper() );
        $this->addExtension( new \Twig_Extensions_Extension_I18n() );

        $this->addExtension( new PHPFunctionsExtension() );
        $engine = new MarkdownEngine\MichelfMarkdownEngine();
        $this->addExtension( new MarkdownExtension( $engine ) );
    }
}
