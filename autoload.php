<?php


$prefixes = array
(
	'Twig_Extensions_' => __DIR__ . '/../../twig-extensions/lib',
	'Twig_'            => __DIR__ . '/../../twig/lib',
);

$vendors_path = __DIR__ . '/../../';
$namespaces = array
(
	'Symfony' => $vendors_path,
	'Pimple' => $vendors_path,
	'natxet' => $vendors_path
);

/* Don't edit bellow this line */

$apps_path = __DIR__ . '/../../../app/';

foreach ( new \DirectoryIterator( $apps_path ) as $file )
{
	if( $file->isDir() )
	{
		$namespaces[$file->getFilename()] = $apps_path;
	}
}

require( __DIR__ . '/../../Symfony/Component/ClassLoader/UniversalClassLoader.php' );
$classLoader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$classLoader->registerNamespaces( $namespaces );
$classLoader->registerPrefixes( $prefixes );
$classLoader->register();
