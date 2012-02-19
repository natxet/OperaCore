<?php

namespace natxet\OperaCore;

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
	protected $uri;

	public function __construct( $c, $routes )
	{
		$this->i18n = $c['I18n'];
		$this->uri  = $c['Request']->getRequestUri();

		$this->parseRoutes( $routes );
	}

	public function parseRoutes( $routes )
	{
		// foreach route already in the attribute
		foreach ( $routes as $k => $v )
		{
			// first, translate  the route
			$routes[$k]['pattern_i18n'] = $this->i18nParse( $routes[$k]['pattern'] );

			$regex = $routes[$k]['pattern_i18n'];

			// Find al params in the pattern for the route
			preg_match_all( '/%([a-z_]+)%/', $regex, $params_matches );
			$routes[$k]['params'] = $params_matches[1];

			// Foreach param, save it and replace it in the pattern with a regex
			foreach ( $routes[$k]['params'] as $param )
			{
				$p_regex = ( array_key_exists( 'p.' . $param, $v ) ) ? $v['p.' . $param] : self::DEFAULT_PARAM_REGEX;
				$regex   = str_replace( "%$param%", "($p_regex)", $regex );
			}

			// It prepares the whole regex with limiters and adds profile suffix
			$regex = '/^' . str_replace( '/', '\\/', $regex ) . '(?:' . self::PROFILE_SUFFIX . ')?$/';

			// Saves the regex for later access
			$routes[$k]['pattern_regex'] = $regex;
		}

		$this->routes = $routes;
	}

	public function i18nParse( $string )
	{
		return $this->i18n->parseTranlations( $string );
	}

	public function getPath( $route, $params = array() )
	{
		$r = $this->routes[$route];
		$uri = $r['pattern_i18n'];

		foreach ( $r['params'] as $param )
		{
			$param_value = array_key_exists( $param, $params ) ? $params[$param] : '';
			$uri = str_replace( "%$param%", $param_value, $uri );
		}
		// TODO: chapuza
		$uri = str_replace( array('(?:/p)?', '(?::)?','(?:',')?'), '', $uri );

		return $uri;
	}

	public function getRoute()
	{
		foreach ( $this->routes as $k => $v )
		{
			if ( preg_match_all( $v['pattern_regex'], $this->uri, $matches ) )
			{
				$route_key = $k;
				break;
			}
		}

		if ( !isset( $route_key ) )
		{
			$route_key = '404';
			$matches   = array();
		}

		$route  = $this->routes[$route_key];
		$params = array();

		if ( isset( $route['params'] ) && $route['params'] && is_array( $route['params'] ) )
		{
			array_shift( $matches );

			for ( $i = 0; $i < count( $route['params'] ); $i++ )
			{
				if ( isset( $matches[$i] ) )
				{
					$params[$route['params'][$i]] = $matches[$i][0];
				}
			}
		}

		define( 'PROFILE', preg_match( '/.*' . self::PROFILE_SUFFIX . '$/', $this->uri ) );

		return array( $route['controller'], $route['action'], $params );
	}
}
