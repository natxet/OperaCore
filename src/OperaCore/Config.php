<?php

namespace OperaCore;

class Config
{
	const INI_FILE_EXTENSION = 'ini'; // ini config files extension
    const PHP_FILE_EXTENSION = 'php'; // php config files extension
	const ENV_PREFIX = 'APP_'; // prefix for env configs passed by apache

	protected $domains; // array with the config key->value itself
	protected $domains_parsed; // array with the config key->value itself parsed with apache super configs
	protected $env_params = array();
	protected $env_configs; // array with all args passed by apache

    protected $env;
    protected $path;

	public function __construct( $domains, $env, $path )
	{
        $this->env = $env;
        $this->path = $path;
		foreach ( $domains as $domain ) $this->parse_ini( $domain, $env, $path );

		$this->load_env_vars();
		$this->domains_parsed = $this->parse_env_config( $this->domains );
	}

    /**
     * Strategy for parsing any kind of config file
     * @param $filename complete filename, including path, of the config to load
     * @param $ext the extension (php|ini)
     *
     * @return array The desired config
     */
    public function parse_file( $filename, $ext)
    {
        switch( $ext ) {
            case 'ini': return parse_ini_file( $filename, 1 ); break;
            default: return require( $filename );
        }
    }

    /**
     * Called from parse_ini and parse_php, uses parse_file, extends the environments config file
     *
     * @param string $domain the basename of the file to parse
     * @param null   $env the environment (prod|dev)
     * @param null   $path the path to the config files of the app
     * @param string $ext the extension (php|ini)
     *
     * @return array The desired config
     */
    protected function parse_config( $domain, $env = null, $path = null, $ext = 'ini' )
    {
        if( empty( $env ) ) $env = $this->env;
        if( empty( $path ) ) $path = $this->path;

        if( !file_exists( "$path/$domain.$ext" ) )
        {
            if( DEBUG ) echo "Config error: file does not exists $path/$domain.$ext";
            return false;
        }
        $this->domains[$domain] = $this->parse_file( "$path/$domain.$ext", $ext );

        // in environments different than pro, you can extend config file
        if ( Bootstrap::PRODUCTION_ENV !== $env )
        {
            $ext_filename = "$path/$domain.$env.$ext"; // extended filename

            if ( file_exists( $ext_filename ) )
            {
                $this->domains[$domain] = Helper::array_merge_recursive_simple(
                    $this->domains[$domain], $this->parse_file( $ext_filename, $ext )
                );
            }
        }
        return $this->domains[$domain];
    }

    /**
     * Parse a config in a php file
     *
     * @param string $domain the basename of the file to parse
     * @param null   $env the environment (prod|dev)
     * @param null   $path the path to the config files of the app
     *
     * @return array
     */
    public function parse_php( $domain, $env = null, $path = null )
    {
        return $this->parse_config( $domain, $env, $path, self::PHP_FILE_EXTENSION);
    }

    /**
     * Parse a config in a php file
     *
     * @param string $domain the basename of the file to parse
     * @param null   $env the environment (prod|dev)
     * @param null   $path the path to the config files of the app
     *
     * @return array
     */
    public function parse_ini( $domain, $env = null, $path = null )
    {
        return $this->parse_config( $domain, $env, $path, self::INI_FILE_EXTENSION);
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

	public function get( $domain, $group = null, $key = null )
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

	public function write_ini( $sectionsarray, $filename, $prefix = null )
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
