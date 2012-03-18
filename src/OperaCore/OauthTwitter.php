<?php

namespace OperaCore;

class OauthTwitter implements Oauth
{
	/**
	 * @var \TwitterOauth
	 */
	protected $provider;

	/**
	 * @var array the config
	 */
	protected $config = array();

	/**
	 * @var array|null
	 */
	protected $user_data;

	/**
	 * @param Container $c
	 */
	public function load( Container $c )
	{
		if (!session_id()) session_start();

		$this->config =  $c['Config']->get( 'main', 'twitter' );
		$this->verify();
		$this->connect();
	}

	/**
	 * @param $access_token array with two elements:
	 */
	protected function connect( $access_token = NULL )
	{
		$consumer_key = $this->config['consumer_key'];
		$consumer_secret = $this->config['consumer_secret'];

		if( !isset( $access_token) && isset( $_SESSION['access_token'] ) )
		{
			$access_token = $_SESSION['access_token'];
		}

		$token = isset( $access_token['oauth_token'] ) ? $access_token['oauth_token'] : NULL;
		$secret = isset( $access_token['oauth_token_secret'] ) ? $access_token['oauth_token_secret'] : NULL;

		//if( !isset( $this->provider ) )
		require_once VENDOR_PATH . 'twitter/twitteroauth/twitteroauth.php';
		$this->provider = new \TwitterOAuth( $consumer_key, $consumer_secret, $token, $secret );

		/*
		 // If method is set change API call made. Test is called by default.
		$content = $this->provider->get('account/rate_limit_status');
		echo "Current API hits remaining: {$content->remaining_hits}.";

		// Get logged in user to help with tests.
		$user = $this->provider->get('account/verify_credentials');

		 */
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	protected function verify()
	{
		if( !isset( $_REQUEST['oauth_token'] ) ) return false;

		if( isset( $_REQUEST['oauth_token'] ) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token'] )
		{
			$_SESSION['oauth_status'] = 'oldtoken';
			throw new \Exception('Problem with Twitter tokens');
		}

		/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
		$access_token = array(
			'oauth_token' => $_SESSION['oauth_token'],
			'oauth_token_secret' => $_SESSION['oauth_token_secret']
		);
		$this->connect( $access_token );

		/* Save the access tokens. Normally these would be saved in a database for future use. */
		$_SESSION['access_token'] = $this->provider->getAccessToken($_REQUEST['oauth_verifier']);

		/* Remove no longer needed request tokens */
		//unset($_SESSION['oauth_token']);
		//unset($_SESSION['oauth_token_secret']);

		return true;
	}

	/**
	 * @return string
	 */
	public function getUserId()
	{
		$user_data = $this->getUserData();
		return $user_data['id'];
	}

	/**
	 * @return array
	 */
	public function getUserData()
	{
		if( !$this->user_data )
		{
			$user_data = $this->provider->get('account/verify_credentials');

			$this->user_data = array(
				'username' => $user_data['screen_name'],
				'email' => NULL,
				'name' => $user_data['name'],
				'first_name' => NULL,
				'last_name' => NULL
			);
		}

		return $this->user_data;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getLoginUrl()
	{
		/* Get temporary credentials. */
		$request_token = $this->provider->getRequestToken( $this->config['oauth_callback' ] );

		//if( $this->provider->http_code != 200 ) throw new \Exception('Problem connecting to Twitter');

		/* Save temporary credentials to session. */
		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

		return $this->provider->getAuthorizeURL( $_SESSION['oauth_token'] );
	}

	public function isError()
	{
		/*
		$request = $this->c['Request'];
		$error = $request->query->get('error');

		return $error ? $error : NULL;
		*/
	}
}
