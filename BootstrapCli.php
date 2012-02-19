<?php
namespace natxet\OperaCore;
/**
 * User: nacho
 * Date: 09/02/12
 */
class BootstrapCli extends Bootstrap
{
	protected function getEnv( $c )
	{
		global $argv;
		return $argv[2];
	}

	protected function getApp( $c )
	{
		global $argv;
		return $argv[3];
	}
}
