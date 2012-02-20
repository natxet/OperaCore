<?php

namespace natxet\OperaCore;

class Config
{
	const FILE_EXTENSION = 'ini'; // config files extension
	const ENV_PREFIX = 'APP_'; // prefix for env configs passed by apache

	protected $domains; // array with the config key->value itself
	protected $domains_parsed; // array with the config key->value itself parsed with apache super configs
	protected $env_params = array();
	protected $env_configs; // array with all args passed by apache

	public function __construct( $domains, $env, $path )
	{
		foreach ( $domains as $domain ) $this->parse_ini( $domain, $env, $path );

		$this->load_env_vars();
		$this->domains_parsed = $this->parse_env_config( $this->domains );
	}

	protected function parse_ini( $domain, $env, $path )
	{
		$ext                    = self::FILE_EXTENSION;
		if( !file_exists( "$path/$domain.$ext" ) ) return false;
		$this->domains[$domain] = parse_ini_file( "$path/$domain.$ext", 1 );

		// in environments different than pro, you can extend config file
		if ( Bootstrap::PRODUCTION_ENV !== $env )
		{

			$ext_filename = "$path/$domain.$env.$ext"; // extended filename

			if ( file_exists( $ext_filename ) )
			{

				$this->domains[$domain] = Helper::array_merge_recursive_simple(
					$this->domains[$domain], parse_ini_file( $ext_filename, 1 )
				);
			}
		}

		return true;
	}

	protected function load_env_vars()
	{

		//TODO: use HTTPFoundation
		foreach ( $_SERVER as $k => $v )
		{

			if ( preg_match( '/^' . self::ENV_PREFIX . '/', $k ) )
			{

				$this->env_params[preg_replace( '/^' . self::ENV_PREFIX . '/', '', $k )] = $v;
			}
		}
	}

	protected function parse_env_config( $value )
	{

		if ( is_string( $value ) && preg_match( '/^%.*%$/', $value ) )
		{

			$env_key = preg_replace( '/%/', '', $value );

			if ( array_key_exists( $env_key, $this->env_params ) )
			{

				$value = $this->env_params[$env_key];
			}
		}
		elseif ( is_array( $value ) )
		{

			foreach ( $value as $k => $v )
			{

				$value[$k] = $this->parse_env_config( $v );
			}
		}
		return $value;
	}

	public function get( $domain, $group = NULL, $key = NULL )
	{

		if ( is_null( $group ) )
		{
			if( isset($this->domains_parsed[$domain])
				&& is_array($this->domains_parsed[$domain]) )
			{
				return $this->domains_parsed[$domain];
			}
		}
		elseif ( is_null( $key ) )
		{
			if( isset($this->domains_parsed[$domain][$group])
				&& is_array($this->domains_parsed[$domain][$group]) )
			{
				return $this->domains_parsed[$domain][$group];
			}
		}
		else
		{
			if( isset($this->domains_parsed[$domain][$group][$key]) )
			{
				return $this->domains_parsed[$domain][$group][$key];
			}
		}

		return array();
	}

	public function write_ini( $sectionsarray, $filename, $prefix = NULL )
	{
		$file_contents = $this->array2ini( $sectionsarray );
		if( $prefix ) $file_contents = $prefix . "\n" . $file_contents;
		return file_put_contents( $filename, $file_contents );
	}

	/**
	 * Generated the output of the ini file, suitable for echo'ing or
	 * writing back to the ini file.
	 *
	 * @param string $sectionsarray array of ini data
	 *
	 * @return string
	 */
	protected function array2ini( $sectionsarray )
	{
		$linebreak = "\n";
		$content  = '';
		$sections = '';
		$globals  = '';
		if ( !empty( $sectionsarray ) )
		{
			// 2 loops to write `globals' on top, alternative: buffer
			foreach ( $sectionsarray as $section => $item )
			{
				if ( !is_array( $item ) )
				{
					$value = $this->normalizeValue( $item );
					$globals .= $section . ' = ' . $value . $linebreak;
				}
			}
			$content .= $globals;
			foreach ( $sectionsarray as $section => $item )
			{
				if ( is_array( $item ) )
				{
					$sections .= $linebreak . '[' . $section . ']' . $linebreak;
					foreach ( $item as $key => $value )
					{
						if ( is_array( $value ) )
						{
							foreach ( $value as $arrkey => $arrvalue )
							{
								$arrvalue = $this->normalizeValue( $arrvalue );
								$arrkey   = $key . '[' . $arrkey . ']';
								$sections .= $arrkey . ' = ' . $arrvalue . $linebreak;
							}
						}
						else
						{
							$value = $this->normalizeValue( $value );
							$sections .= $key . ' = ' . $value . $linebreak;
						}
					}
				}
			}
			$content .= $sections;
		}
		return $content;
	}

	/**
	 * normalize a Value by determining the Type
	 *
	 * @param string $value value
	 *
	 * @return string
	 */
	protected function normalizeValue( $value )
	{
		if ( is_bool( $value ) )
		{
			$value = ( $value ) ? 1 : 0;
		}
		elseif ( !is_numeric( $value ) )
		{
			$value = '"' . $value . '"';
		}
		return $value;
	}
}
