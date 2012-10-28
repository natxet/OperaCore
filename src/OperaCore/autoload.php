<?php
require_once( VENDOR_PATH . 'autoload.php' );

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();

$namespaces = array(
	'Symfony\Component' => VENDOR_PATH . 'symfony/class-loader',
	'OperaCore'         => VENDOR_PATH . 'natxet/OperaCore/src',
);

// this is for loading the namespace of every app we have in the apps directory
foreach ( glob( APPS_PATH . '*', GLOB_ONLYDIR ) as $namespace )
{
	$namespaces[basename( $namespace )] = APPS_PATH;
}

$loader->registerNamespaces( $namespaces );

$prefixes = array(
	'Pimple'            => VENDOR_PATH . 'fabpot/pimple/lib',
	'Twig'              => VENDOR_PATH . 'fabpot/twig/lib',
	'Twig_Extensions'   => VENDOR_PATH . 'natxet/twig-extensions/lib',
);
$loader->registerPrefixes( $prefixes );

$loader->register();
