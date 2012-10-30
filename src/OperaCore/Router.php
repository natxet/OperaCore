<?php

namespace OperaCore;

/**
 * User: nacho
 * Date: 22/01/12
 */
class Router
{
	const PROFILE_SUFFIX      = '\?profile';
	const DEFAULT_PARAM_REGEX = '[a-z0-9-]+';

	/**
	 * @var array
	 */
	protected $routes;

	/**
	 * @var I18n
	 */
	protected $i18n;

	/**
	 * @var string
	 */
	protected $scheme;

	/**
	 * @var string
	 */
	protected $hostname;

	/**
	 * @var string
	 */
	protected $port;

	/**
	 * @var string
	 */
	protected $domain;

	/**
	 * @var string
	 */
	protected $subdomain;

	/**
	 * @var string
	 */
	protected $uri;

	public function __construct( $c, $routes )
	{
		$this->i18n = $c['I18n'];
		$this->processRequest( $c['Request'] );

		$this->parseRoutes( $routes );
	}

	public function parseRoutes( $routes )
	{
		// foreach route already in the attribute
		foreach ( $routes as $route_key => $route_config )
		{
			// first, translate  the route
			$route_config['pattern_i18n'] = $this->i18nParse( $route_config['pattern'] );

			if ( array_key_exists( 'subdomain', $route_config ) )
			{
				$route_config['subdomain_i18n'] = $this->i18nParse( $route_config['subdomain'] );
			}

			$routes[$route_key] = $this->separateParamsFromRegex( $route_config );
		}

		$this->routes = $routes;
	}

	protected function separateParamsFromRegex( $r )
	{
		// This will be the regex for comparing, not for using for generating urls: just for finding the route matching
		$regex = $r['pattern_i18n'];
		// subdomains, only if defined
		$s_regex = array_key_exists( 'subdomain', $r ) ? $r['subdomain_i18n'] : '';

		// Find al params in the pattern for the route
		preg_match_all( '/%([a-z_]+)%/', $s_regex . '|' . $regex, $params_matches );
		$r['params'] = $params_matches[1];

		// Foreach param, save it and replace it in the pattern with a regex
		foreach ( $r['params'] as $param )
		{
			// if we defined a type of regex for this variable we will substitute it. If not, the common regex (a-z0-9-)
			$p_regex = ( array_key_exists( 'p.' . $param, $r ) ) ? $r['p.' . $param] : self::DEFAULT_PARAM_REGEX;
			$regex   = str_replace( "%$param%", "($p_regex)", $regex );
			$s_regex = str_replace( "%$param%", "($p_regex)", $s_regex );
		}

		// It prepares the whole regex with limiters and adds profile suffix
		$regex   = '/^' . str_replace( '/', '\\/', $regex ) . '(?:' . self::PROFILE_SUFFIX . ')?$/u';
		$s_regex = '/^' . str_replace( '/', '\\/', $s_regex ) . '$/u';

		// Saves the regex for later access
		$r['pattern_regex'] = $regex;

		if ( array_key_exists( 'subdomain', $r ) )
		{
			$r['subdomain_regex'] = $s_regex;
		}

		return $r;
	}

	public function i18nParse( $string )
	{
		return $this->i18n->parseTranlations( $string );
	}

	/**
	 * @param string $route_key the route key
	 * @param array $params
	 * @param bool $absolute if the url must be absolute (protocol://domain/uri) or just the uri with /x relative
	 * @return mixed|string
	 */
	public function getPath( $route_key, $params = array(), $absolute = true )
	{
		if( !array_key_exists( $route_key, $this->routes ) ) return '';

		$r        = $this->routes[$route_key];
		$uri      = $r['pattern_i18n'];
		$subdomain = empty( $r['subdomain_i18n'] ) ? '' : "{$r['subdomain_i18n']}.";
		$hostname = isset( $r['subdomain'] ) ? $subdomain . $this->domain : $this->domain;
		if( $absolute )
		{
			$path = $this->composeAbsoluteURL( $hostname, $uri );
		}
		else
		{
			$path = $uri;
		}

		foreach ( $r['params'] as $param )
		{
			$param_value = array_key_exists( $param, $params ) ? $params[$param] : '';
			$path        = str_replace( "%$param%", $param_value, $path );
		}
		// TODO: chapuza
		$path = str_replace( array( '(?:&p=)?', '(?::)?', '(?:', ')?' ), '', $path );
		$path = stripslashes( $path );
		return $path;
	}

	protected function composeAbsoluteURL( $hostname, $uri )
	{
		$port = ( '80' == $this->port ) ? '' : ":{$this->port}";
		return $this->scheme . '://' . $hostname . $port . $uri;
	}

	/**
	 * @param $request \Symfony\Component\HttpFoundation\Request
	 */
	protected function processRequest( $request )
	{
		$this->uri       = urldecode( $request->getRequestUri() );
		$this->hostname  = urldecode( $request->getHost() );
		$this->port      = $request->getPort();
		$this->scheme    = $request->getScheme();
		$this->subdomain = preg_replace( '/\.?' . str_replace('.', '\\.', BASE_HOSTNAME) . '/' , '', $this->hostname );
		$this->domain    = BASE_HOSTNAME;
	}

	public function getRoute()
	{
		define( 'PROFILE', preg_match( '/.*' . self::PROFILE_SUFFIX . '$/', $this->uri ) );

		foreach ( $this->routes as $k => $v )
		{
			$profile = array( 'route' => $k );
			// so this is a specific route for a subdomain
			if ( isset( $v['subdomain'] ) )
			{
				// if the subdomain matches the config
				if ( preg_match_all( $v['subdomain_regex'], $this->subdomain, $matches_subdomain ) )
				{
					array_shift( $matches_subdomain );
					$profile['subdomain'] = "FOUND! $this->subdomain subdomain DOES match " . $v['subdomain_regex'];
				}
				else
				{
					// if not, we profile and go for next route_key (continue foreach)
					$profile['subdomain'] = "Not found: $this->subdomain subdomain does not match " . $v['subdomain_regex'];
					Profile::collect( 'Route', $profile );
					continue;
				}
			}
			else
			{
				// if subdomain was not configured
				$matches_subdomain = array();
			}

			// if the uri matches the config
			if ( preg_match_all( $v['pattern_regex'], $this->uri, $matches ) )
			{

				$profile['uri'] = "FOUND! $this->uri uri DOES match " . $v['pattern_regex'];

				array_shift( $matches );
				$matches   = array_merge( $matches_subdomain, $matches );
				$route_key = $k;

				Profile::collect( 'Route', $profile );
				break;
			}

			$profile['uri'] = "Not found: $this->uri uri does not match " . $v['pattern_regex'];
			Profile::collect( 'Route', $profile );
		}

		if ( !isset( $route_key ) )
		{
			throw new \OperaCore\Exception\PageNotFound( $this->uri );
		}

		$route  = $this->routes[$route_key];
		$params = array();

		if ( isset( $route['params'] ) && $route['params'] && is_array( $route['params'] ) )
		{
			for ( $i = 0; $i < count( $route['params'] ); $i++ )
			{
				if ( isset( $matches[$i] ) )
				{
					$params[$route['params'][$i]] = $matches[$i][0];
				}
			}
		}

		return array( $route['controller'], $route['action'], $params );
	}
}
