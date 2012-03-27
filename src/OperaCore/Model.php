<?php

namespace OperaCore;

/**
 * The Model (MVC)
 */
abstract class Model
{
	/**
	 * This object should extend PDO
	 * @var Database The database connection object
	 */
	protected $db; // PDO Object

	/**
	 * @param $c Container
	 */
	public function __construct( $c )
	{
		$this->db = $c['Database'];
		$this->container = $c;
	}

	/**
	 * @param $statement    string The SQL statement to execute
	 * @param $params array The associative array for binding params
	 * @param $profile string The profile name
	 *
	 * @return bool the result
	 */
	public function write( $statement, $params = array(), $profile = 'write' )
	{
		$offset = microtime( true );

		$db = $this->db->$profile;

		$sth = $db->prepare( $statement );
		$res = $sth->execute( $params );
		$affected_rows = $sth->rowCount();

		$this->profile_collect( $offset, $statement, $params, $profile );

		return $affected_rows;
	}

	/**
	 * @param $statement    string The SQL statement to execute
	 * @param $params array The associative array for binding params
	 * @param $profile string The profile name
	 *
	 * @return array Associative array with all the results
	 */
	public function fetchAll( $statement, $params = array(), $profile = 'read' )
	{
		$offset = microtime( true );

		$db = $this->db->$profile;

		$sth = $db->prepare( $statement );
		$sth->execute( $params );
		$res = $sth->fetchAll( \PDO::FETCH_ASSOC );

		$this->profile_collect( $offset,  $statement, $params, $profile );

		return $res;
	}

	/**
	 * @param $statement    string The SQL statement to execute
	 * @param $params array The associative array for binding params
	 * @param $profile string The profile name
	 *
	 * @return array Associative array with the first result
	 */
	public function fetchOne( $statement, $params = array(), $profile = 'read' )
	{
		$offset = microtime( true );

		$db = $this->db->$profile;

		$sth = $db->prepare( $statement );
		$sth->execute( $params );
		$res = $sth->fetch( \PDO::FETCH_ASSOC );

		$this->profile_collect( $offset,  $statement, $params, $profile );

		return $res;
	}

	public function fetchOneColumn( $statement, $params, $profile = 'read' )
	{
		$res = $this->fetchOne( $statement, $params, $profile );
		foreach( $res as $k => $v ) return $v;
		return null;
	}

	public function profile_collect( $offset, $statement, $params = array(), $profile = 'read' ) {

		if ( DEBUG )
		{
			$backtrace = debug_backtrace( 3 );
			if( is_object($backtrace[2]['object']) ) $backtrace[2]['classname'] = get_class( $backtrace[2]['object']);
			$params2 = $params;
			array_walk( $params2, function( &$value ) {
					if( !is_numeric( $value ) ) $value = "'$value'";
				} );
			$statement = str_replace( array_keys( $params2 ), array_values( $params2 ), $statement );
			Profile::collect( 'Models', array(
			    'profile'      => $profile,
				'statement'    => $statement,
				'params'       => $params,
				'miliseconds'  => (int) ( ( microtime( true ) - $offset ) * 1000000 ) / 1000,
				'backtrace'    => $backtrace[2]
			) );
		}
	}

	protected function prepare_params( $params, $mandatory, $optional )
	{
		$final_params = array();

		foreach( $mandatory as $field => $type )
		{
			if( !isset( $params[$field] ) ) throw new \InvalidArgumentException();
			$final_params[$field] = $this->prepare_param_type( $params[$field], $type );
		}

		foreach( $optional as $field => $type )
		{
			$final_params[$field] = isset( $params[$field] ) ?
				$this->prepare_param_type( $params[$field], $type ) : NULL;

		}

		return $final_params;
	}

	protected function prepare_param_type( $param, $type )
	{
		switch( $type )
		{
			case 'int': return (int) $param;
			default: return $param;
		}
	}
}

