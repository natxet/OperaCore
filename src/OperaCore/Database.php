<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 03/02/12
 */
class Database extends \PDO
{


	public function __construct( $c )
	{

		$p = $c['Config']->get( 'main', 'db' );

		$connection = ( ( 'localhost' == $p['hostname'] ) && ( isset( $p['socket'] ) && $p['socket'] ) ) ? ';unix_socket=' . $p['socket'] : ';host=' . $p['hostname'];

		parent::__construct(
			'mysql:dbname=' . $p['database'] . $connection, $p['username'], $p['password'],
			array( self::MYSQL_ATTR_INIT_COMMAND  => 'SET NAMES utf8' )
		);

		$this->setAttribute( self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION );
	}
}
