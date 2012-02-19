<?php
namespace natxet\OperaCore;
/**
 * User: nacho
 * Date: 07/02/12
 */

abstract class CliScript
{
	/**
	 * @var Container
	 */
	protected $container;

	public function __construct( $argv, $container )
	{
		$this->container = $container;
		$this->run( $argv );
	}

	abstract protected function run( $argv );
}
