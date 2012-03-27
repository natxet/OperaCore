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

	/**
	 * @return string
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
		$user_data = $this->provider->api( '/me' );

		return array(
			'id'         => $user_data['id'],
			'username'   => $user_data['username'],
			'email'      => $user_data['email'],
			'name'       => $user_data['name'],
			'first_name' => $user_data['first_name'],
			'last_name'  => $user_data['last_name']
		);
	}

	/**
	 * @return string the url for login
	 */
	public function getLoginUrl()
	{
		return $this->provider->getLoginUrl( $this->config['uri_params'] );
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
