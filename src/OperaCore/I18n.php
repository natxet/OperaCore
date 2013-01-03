<?php

namespace OperaCore;

/**
 * User: nacho
 * Date: 31/01/12
 */
class I18n
{
	const MESSAGES_DOMAIN = 'messages';
	const ROUTES_DOMAIN   = 'routes';

	protected $_language;
	protected $_encoding;
	protected $_locale_path;
	protected $_domain = "*";

	/**
	 * @param $c Container
	 */
	public function __construct( $c )
	{
		$this->guessLanguage( $c );

		$this->_locale_path = $c['i18n_path'];
		$this->_encoding    = $c['Config']->get( 'main', 'app', 'encoding' );

		$this->set_locale( $this->_language );
		$this->definitions( $c );
	}


	/**
	 * @param $request \Symfony\Component\HttpFoundation\Request
	 * @param $config Config
	 * @return null|string
	 */
	public function getBrowserLanguage( $request, $config )
	{
		$accepted_languages = $request->getLanguages();

		$langs = array();
		foreach( array_keys( $config->get( 'main', 'locales_names' ) ) as $l) {
			$langs[$l] = $l;
			$langs[substr( $l, 0, 2 )] = $l;
		}

		foreach( $accepted_languages as $possible_language )
		{
			if( array_key_exists($possible_language, $langs)) return $langs[$possible_language];
		}
		return NULL;
	}

	/**
	 *
	 * @param $c Container
	 */
	protected function guessLanguage( $c )
	{

		$url_part         = $c['Config']->get( 'main', 'locale', 'url_part' );
		$locales_patterns = $c['Config']->get( 'main', 'locales_patterns' );

		switch ( $url_part )
		{
			//TODO: program all cases 'uri_prefix', 'uri_postfix', 'subdomain'
			case 'hostname':
				$subject = $c['Request']->getHttpHost();
				break;
			default:
				$subject = '';
		}
		foreach ( $locales_patterns as $possible_locale => $pattern )
		{
			// We add possible subdomains, and put slashes in points
			if ( preg_match( '/^' . '(?:.+\.)?' . str_replace( '.', '\\.', $pattern ) . '$/', $subject ) )
			{
				$language = $possible_locale;
				break;
			}
		}
		if ( !isset( $language ) ) $language = $c['Config']->get( 'main', 'locale', 'default' );

		$this->_language = trim( $language );
	}

	protected function definitions( $c ) {

		define( 'LOCALE', $this->_language );
		define( 'LANG', substr( $this->_language, 0, 2 ) );
		define( 'BASE_HOSTNAME', $c['Config']->get( 'main', 'locales_patterns', LOCALE ) );

		putenv( 'LANGUAGE=' . LOCALE );
		putenv( 'LANG=' . LOCALE );
		putenv( 'LC_ALL=' . LOCALE );
	}

	protected function set_locale( $language)
	{
		if ( !is_scalar( $language ) )
		{
			$type = gettype( $language );
			throw new \InvalidArgumentException( "Language should be a scalar variable and is a $type" );
		}

		$lang = substr( $language, 0, 2 );

		if ( !defined( 'LC_MESSAGES' ) ) define( 'LC_MESSAGES', 6 );

		$setlocale_res = setlocale(
			LC_ALL, $language . ".".strtolower( str_replace( '-', '', $this->_encoding ) ), $language . ".".strtoupper( str_replace( '-', '', $this->_encoding ) ), $language . ".".strtolower( $this->_encoding ),
			$language . ".".strtoupper($this->_encoding), $language, $lang
		);
		if ( ( $setlocale_res != $language && $lang == $setlocale_res ) || empty( $setlocale_res ) )
		{
			throw new \Exception( sprintf(
				"Tried: setlocale to ' % s', but could only set to ' % s'.", $language, $setlocale_res
			) );
		}

		$bindtextdomain_res = bindtextdomain( self::MESSAGES_DOMAIN, $this->_locale_path );
		if ( empty( $bindtextdomain_res ) ) throw new \Exception( sprintf(
			"Tried: bindtextdomain, ' % s', to directory, ' % s', but received ' % s'", self::MESSAGES_DOMAIN,
			$this->_locale_path, $bindtextdomain_res
		) );
		bind_textdomain_codeset( self::MESSAGES_DOMAIN, $this->_encoding );

		$bindtextdomain_res = bindtextdomain( self::ROUTES_DOMAIN, $this->_locale_path );
		if ( empty( $bindtextdomain_res ) ) throw new \Exception( sprintf(
			"Tried: bindtextdomain, ' % s', to directory, ' % s', but received ' % s'", self::ROUTES_DOMAIN,
			$this->_locale_path, $bindtextdomain_res
		) );
		bind_textdomain_codeset( self::ROUTES_DOMAIN, $this->_encoding );

		$textdomain_res = textdomain( self::MESSAGES_DOMAIN );
		if ( empty( $textdomain_res ) ) throw new \Exception( sprintf(
			"Tried: set textdomain to ' % s', but got ' % s'", $this->_domain, $textdomain_res
		) );
	}

	public function parseTranlations( $string )
	{
		return preg_replace( '/{{(.+?)}}/e', "dgettext( self::ROUTES_DOMAIN, '\\1')", $string );
	}
}
