<?php

if (!class_exists('Composer\\Autoload\\ClassLoader', false)) {
	require( VENDOR_PATH .'/.composer/ClassLoader.php' );
}

$__composer_autoload_init = function() {

	$loader = new \Composer\Autoload\ClassLoader();

	$map = require (VENDOR_PATH .'/.composer/autoload_namespaces.php');

	foreach ($map as $namespace => $path) {
		//var_dump($namespace, $path);
		$loader->add($namespace, $path);
	}

	$apps_path = realpath( __DIR__ . '/../../../app/' );

	foreach ( new \DirectoryIterator( $apps_path ) as $file )
	{
		if( $file->isDir() )
		{
			$loader->add( $file->getFilename(), $apps_path);
		}
	}

	$loader->register();

	return $loader;
};

return $__composer_autoload_init();


$apps_path = __DIR__ . '/../../../app/';

foreach ( new \DirectoryIterator( $apps_path ) as $file )
{
	if( $file->isDir() )
	{
		$namespaces[$file->getFilename()] = $apps_path;
	}
}

