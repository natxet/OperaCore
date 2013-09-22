<?php

namespace OperaCore;

abstract class AdminController extends Controller
{
    /**
     * @var array A list of allowed actions without auth
     */
    protected $whitelisted_actions = array();

    protected $session_key = 'auth_user';

    /**
     * @return bool
     */
    protected function isAuthenticated()
    {
        return ( $this->getAuthUser() );
    }

    /**
     * @return mixed
     */
    protected function getAuthUser()
    {
        return $this->getSessionVar( $this->session_key );
    }

    /**
     * @param array $user_data
     */
    protected function setAuthUser( array $user_data )
    {
        $this->setSessionVar( $this->session_key, $user_data );
    }

    /**
     * unsets the auth user session information
     */
    protected function unSetAuthUser()
    {
        $this->unsetSessionVar( $this->session_key );
    }

    /**
     * Checks the user and password, if match, stores user info in session and returns true. If not, returns false.
     *
     * @param string $username the username
     * @param string $password the password
     *
     * @return bool
     */
    protected function doLogin( $username, $password )
    {
        if ($user_data = $this->checkLogin( $username, $password )) {
            $this->setAuthUser( $user_data );
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unsets the login information from the session
     */
    protected function doLogout()
    {
        $this->unSetAuthUser();
    }

    /**
     * @param $username
     * @param $password
     *
     * @return mixed array with user data OR false
     */
    abstract protected function checkLogin( $username, $password );

    /**
     * @param $action    string
     * @param $params    array
     * @param $route_key string
     */
    public function action( $action, $params = array(), $route_key = '' )
    {
        if (!in_array( $action, $this->whitelisted_actions ) && !$this->isAuthenticated()) {
            throw new \OperaCore\Exception\Forbidden('Enter username and password to continue.');
        }
        parent::action( $action, $params, $route_key = '' );
    }

}
