<?php

namespace OperaCore;

class Helper
{
	static public function array_merge_recursive_simple()
	{
		if ( func_num_args() < 2 )
		{
			trigger_error( __CLASS__ . '::' . __FUNCTION__ . ' needs two or more array arguments', E_USER_WARNING );
			return;
		}

		$arrays = func_get_args();
		$merged = array();

		while ( $arrays )
		{
			$array = array_shift( $arrays );

			if ( !is_array( $array ) )
			{
				trigger_error( __CLASS__ . '::' . __FUNCTION__ . ' encountered a non array argument', E_USER_WARNING );
				return;
			}

			if ( !$array )
			{
				continue;
			}

			foreach ( $array as $key => $value )
			{
				if ( is_string( $key ) )
				{
					if ( is_array( $value ) && array_key_exists( $key, $merged ) && is_array( $merged[$key] ) )
					{
						$merged[$key] = call_user_func( __CLASS__ . '::' . __FUNCTION__, $merged[$key], $value );
					}
					else
					{
						$merged[$key] = $value;
					}
				}
				else
				{
					$merged[] = $value;
				}
			}
		}

		return $merged;
	}

	public function format_phone_number( $number )
	{
		return implode( ' ', str_split( $number, 3 ) );
	}

	static function format_uri( $string, $separator = '-' )
	{
		$accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
		$special_cases = array( '&' => 'and', "'" => "");
		$string = mb_strtolower( trim( $string ), 'UTF-8' );
		$string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
		$string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
		$string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
		$string = preg_replace("/[$separator]+/u", "$separator", $string);
		return $string;
	}

	static function markdown( $string )
	{
		include_once( VENDOR_PATH . 'twig/extensions/lib/Twig/Extensions/Markdown/markdown.php');
		if( function_exists( 'Markdown' ) ) return \Markdown( $string );
		else return $string;
	}
}
