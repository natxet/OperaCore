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
     * Gets the browsers preferred locale (f.i. en_US) only if it's in the site accepted locales (at least if the
     * first two letters of the browsers preferred locales match with any site locales first two letters)
     *
     * @param $c Container
     *
     * @return null|string
     */
    static function getCompatibleBrowserLocale( Container $c )
    {
        $browser_languages = $c['Request']->getLanguages();
        $site_locales      = array_keys( $c['Config']->get( 'main', 'locales_names' ) );

        $site_languages = array(); // in a two-char format: not the whole locale format
        foreach ($site_locales as $l) {
            $site_languages[$l]                 = $l;
            $site_languages[substr( $l, 0, 2 )] = $l;
        }

        foreach ($browser_languages as $possible_locale) {
            if (array_key_exists( $possible_locale, $site_locales )) {
                return $possible_locale;
            }

            $possible_language = substr( $possible_locale, 0, 2 );
            if (array_key_exists( $possible_language, $site_languages )) {
                return $site_languages[$possible_language];
            }
        }

        return null;
    }

	/**
	 * @param $c
	 *
	 * @throws \Exception
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
			$regex = '/^' . '(?:.+\.)?' . str_replace( '.', '\\.', $pattern ) . '(?:\:[0-9]+)?$/';
			if ( preg_match( $regex, $subject ) )
			{
				$language = $possible_locale;
				break;
			}
		}

		if ( empty( $language ) )
		{
			$language = $c['Config']->get( 'main', 'locale', 'default' );
		}

		if( empty( $language ) )
		{
			throw new \Exception("Hostname unknown: Review [locales_patterns] in main.ini or main.dev.ini");
		}

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
				"Tried: setlocale to '%s', but could only set to '%s'.", $language, $setlocale_res
			) );
		}

		$bindtextdomain_res = bindtextdomain( self::MESSAGES_DOMAIN, $this->_locale_path );
		if ( empty( $bindtextdomain_res ) ) throw new \Exception( sprintf(
			"Tried: bindtextdomain, '%s', to directory, '%s', but received '%s'", self::MESSAGES_DOMAIN,
			$this->_locale_path, $bindtextdomain_res
		) );
		bind_textdomain_codeset( self::MESSAGES_DOMAIN, $this->_encoding );

		$bindtextdomain_res = bindtextdomain( self::ROUTES_DOMAIN, $this->_locale_path );
		if ( empty( $bindtextdomain_res ) ) throw new \Exception( sprintf(
			"Tried: bindtextdomain, '%s', to directory, '%s', but received '%s'", self::ROUTES_DOMAIN,
			$this->_locale_path, $bindtextdomain_res
		) );
		bind_textdomain_codeset( self::ROUTES_DOMAIN, $this->_encoding );

		$textdomain_res = textdomain( self::MESSAGES_DOMAIN );
		if ( empty( $textdomain_res ) ) throw new \Exception( sprintf(
			"Tried: set textdomain to '%s', but got '%s'", $this->_domain, $textdomain_res
		) );
	}

	/**
	* Used at parseTranlations, parses a route message located in the matches of a preg_*
	*
	**/
	public function translateRouteMessage( $matches ) {

		return ( empty( $matches[1] ) ) ? '' : dgettext( self::ROUTES_DOMAIN, $matches[1]);
	}

	/**
	* Parses all the translations in a string, marked between two braces, f.i. /{{hola}}/test.php
	* and translates them, returning the string translated: /hello/test.php
	*
	**/
	public function parseTranlations( $string )
	{
		return preg_replace_callback( '/{{(.+?)}}/u', 'self::translateRouteMessage', $string );
	}
}
