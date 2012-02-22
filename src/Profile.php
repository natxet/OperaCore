<?php

namespace OperaCore;
/**
 * User: nacho
 * Date: 04/02/12
 */
class Profile
{
	static public $collections = array();
	static public $checkpoints = array();
	static public $checkpoints_previous_time = 0;
	static public $checkpoints_previous_memory = 0;

	static public function Collect( $group, $info )
	{
		if ( !isset( self::$collections[$group] ) ) self::$collections[$group] = array();
		self::$collections[$group][] = $info;
	}

	static public function Checkpoint( $message )
	{

		$microtime = microtime( true ) * 1000;
		$memory    = round( memory_get_usage() / 1024 );

		if ( !self::$checkpoints_previous_time )
		{
			self::$checkpoints_previous_time   = $microtime;
			self::$checkpoints_previous_memory = $memory;
		}

		self::$checkpoints[] = array(
			'message'   => $message,
			'time'      => round( $microtime - self::$checkpoints_previous_time ),
			'memory'    => $memory - self::$checkpoints_previous_memory
		);

		self::$checkpoints_previous_time   = $microtime;
		self::$checkpoints_previous_memory = $memory;
	}
}
