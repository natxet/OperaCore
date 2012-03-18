<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 03/02/12
 */
class Database
{
	protected $profiles = array();

	protected $config = array();

	public function __construct( $c )
	{
		$this->config = $c['Config']->get( 'database' );

	}

	protected function connect( $profile )
	{
		$g = $this->config['__global'];
		$p = $this->config[$profile];

		$connection = ( ( 'localhost' == $p['hostname'] ) && ( isset( $p['socket'] ) && $p['socket'] ) ) ? ';unix_socket=' . $p['socket'] : ';host=' . $p['hostname'];

		$this->profiles[$profile] = new \PDO(
			'mysql:dbname=' . $p['database'] . $connection, $p['username'], $p['password'],
			array( \PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES {$g['charset']}" )
		);

		$this->profiles[$profile]->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
	}

	protected function connectOrReturn( $profile )
	{
		if( !isset( $this->profiles[$profile] ) ) $this->connect( $profile );

		return $this->profiles[$profile];
	}

	public function __get( $profile )
	{
		return $this->connectOrReturn( $profile );
	}
}
