<?php

if ( !class_exists( 'Composer\\Autoload\\ClassLoader', false ) )
{
	require( VENDOR_PATH . '/.composer/ClassLoader.php' );
}

$__composer_autoload_init = function()
{
	$loader = new \Composer\Autoload\ClassLoader();

	$map = require ( VENDOR_PATH . '/.composer/autoload_namespaces.php' );

	foreach ( $map as $namespace => $path )
	{
		$loader->add( $namespace, $path );
	}

	foreach ( new \DirectoryIterator( APPS_PATH ) as $file )
	{
		if ( $file->isDir() )
		{
			$loader->add( $file->getFilename(), APPS_PATH );
		}
	}

	$loader->register();

	return $loader;
};

return $__composer_autoload_init();
