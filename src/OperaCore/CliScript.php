<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 07/02/12
 */

abstract class CliScript extends Controller
{
	/**
	 * color green
	 */
	const GREEN = "\033[32m";

	/**
	 * color red
	 */
	const RED   = "\033[31m";

	/**
	 * color cyan
	 */
	const CYAN  = "\033[36m";

	/**
	 * color white (default!)
	 */
	const WHITE = "\033[37m";

	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * Just execute $this->run after setting the container
	 *
	 * @param array $argv The argv array that PHP receives in CLI
	 * @param Container $container container injected
	 */
	public function __construct( $argv, $container )
	{
		$this->setContainer( $container );
		$this->run( $argv );
	}

	/**
	 * This does the job
	 *
	 * @abstract
	 * @param array $argv The argv array that PHP receives in CLI
	 */
	abstract protected function run( $argv );

	/**
	 * If we need more than this, start thinking in extending some CLI class
	 *
	 * @param string $line The line to print
	 * @param string $color The color to use
	 */
	public function output( $line, $color = self::WHITE )
	{
		echo "\n";
		if( self::WHITE != $color ) echo $color;
		echo $line;
		if( self::WHITE != $color ) echo self::WHITE;
	}
}
