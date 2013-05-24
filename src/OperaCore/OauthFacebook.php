<?php

namespace OperaCore;

class OauthFacebook implements Oauth
{
	/**
	 * @var \Facebook
	 */
	protected $provider;

	/**
	 * @var array the config
	 */
	protected $config = array();

	/**
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * @param Container $c
	 */
	public function load( Container $c )
	{
		$this->config                      = $c['Config']->get( 'main', 'facebook' );
		$this->config['session_container'] = $c['Session'];
		$this->request                     = $c['Request'];
		$this->provider                    = new Facebook( $this->config );
	}

	/*
	 * @return string|null
	 */
	public function getUserId()
	{
		return $this->provider->getUser();
	}

	/**
	 * @return array|null
	 */
	public function getUserData()
	{
		return $this->provider->api( '/me' );
	}

	/**
	 * @return array|null
	 */
	public function getUserFriends()
	{
		$friends = $this->provider->api( '/me/friends?fields=first_name,name,gender,locale,birthday' );
        return isset( $friends['data'] ) ? $friends['data'] : array();
	}


	/**
	 * @return string the url for login
	 */
	public function getLoginUrl()
	{
		return $this->provider->getLoginUrl( $this->config['uri_params'] );
	}

	public function getProvider()
	{
		return $this->provider;
	}

	/**
	 * @return string|null
	 */
	public function isError()
	{
		$error = $this->request->query->get( 'error' );

		return $error ? $error : NULL;
	}
}
