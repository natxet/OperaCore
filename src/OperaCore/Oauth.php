<?php

namespace OperaCore;

interface Oauth
{
	public function load( Container $c );

	public function getUserId();

	public function getUserData();

	public function getLoginUrl();

	public function isError();
}
