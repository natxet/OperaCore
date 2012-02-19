<?php

namespace natxet\OperaCore;

/**
 * User: nacho
 * Date: 31/01/12
 */ class I18n
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
		$this->_encoding  = $c['Config']->get( 'main', 'app', 'encoding' );

		$this->init();
	}

	/**
	 *
	 * @param $c Container
	 */
	protected function guessLanguage( $c ) {

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
			if ( preg_match( '/^' . str_replace( '/', '\\/', $pattern ) . '$/', $subject ) )
			{
				$language = $possible_locale;
				break;
			}
		}

		if ( !isset( $language ) ) $language = $c['Config']->get( 'main', 'locale', 'default' );

		$this->_language    = $language;
	}

	protected function init()
	{
		define( 'LOCALE', $this->_language );
		define( 'LANG', substr( $this->_language, 0, 2 ) );

		putenv( 'LANGUAGE=' . LOCALE );
		putenv( 'LANG=' . LOCALE );
		putenv( 'LC_ALL=' . LOCALE );

		if ( !defined( 'LC_MESSAGES' ) ) define( 'LC_MESSAGES', 6 );

		$setlocale_res = setlocale(
			LC_ALL, $this->_language . ".utf8", $this->_language . ".UTF8", $this->_language . ".utf-8",
			$this->_language . ".UTF-8", $this->_language, LANG
		);
		if ( ( $setlocale_res != $this->_language && LANG == $setlocale_res ) || empty( $setlocale_res ) ) {
			throw new \Exception( sprintf(
				"Tried: setlocale to '%s', but could only set to '%s'.", $this->_language, $setlocale_res
			) );
		}

		$bindtextdomain_res = bindtextdomain( self::MESSAGES_DOMAIN, $this->_locale_path );
		if ( empty( $bindtextdomain_res ) ) throw new \Exception( sprintf(
			"Tried: bindtextdomain, '%s', to directory, '%s', but received '%s'", self::MESSAGES_DOMAIN, $this->_locale_path,
			$bindtextdomain_res
		) );
		bind_textdomain_codeset( self::MESSAGES_DOMAIN, $this->_encoding );

		$bindtextdomain_res = bindtextdomain( self::ROUTES_DOMAIN, $this->_locale_path );
		if ( empty( $bindtextdomain_res ) ) throw new \Exception( sprintf(
			"Tried: bindtextdomain, '%s', to directory, '%s', but received '%s'", self::ROUTES_DOMAIN, $this->_locale_path,
			$bindtextdomain_res
		) );
		bind_textdomain_codeset( self::ROUTES_DOMAIN, $this->_encoding );

		$textdomain_res = textdomain( self::MESSAGES_DOMAIN );
		if ( empty( $textdomain_res ) ) throw new \Exception( sprintf(
			"Tried: set textdomain to '%s', but got '%s'", $this->_domain, $textdomain_res
		) );
	}

	public function parseTranlations( $string )
	{
		return preg_replace( '/{{(.+?)}}/e', "dgettext( self::ROUTES_DOMAIN, '\\1')", $string );
	}
}