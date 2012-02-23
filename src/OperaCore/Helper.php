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

	public function paginate_get_array( $total_rows, $current_page, $results_per_page )
	{

		$total_pages = floor( $total_rows / $results_per_page );

		$min_page = ( $current_page < 5 ) ? 1 : $current_page - 5;
		$max_page = ( $min_page + 9 < $total_pages ) ? $min_page + 9 : $total_pages;
		while( ( $min_page > 1 ) && (  $max_page - $min_page + 1 < 9 ) ) {
			$min_page--;
		}

		$array = array(
			'total_rows'       => $total_rows,
			'total_pages'      => $total_pages,
			'current_page'     => $current_page,
			'results_per_page' => $results_per_page
		);

		$array['previous_page'] = ( $current_page > 0 ) ? $current_page - 1 : NULL;
		$array['next_page']     = ( $current_page < $total_pages ) ? $current_page + 1 : NULL;
		$array['pages']         = range( $min_page, $max_page );

		return $array;
	}
}
