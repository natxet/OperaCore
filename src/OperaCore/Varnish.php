<?php

namespace OperaCore;

class Varnish
{

	public function banUrl( $regex )
	{
		exec('varnishadm ban.url ~ "' . $regex . '"');
	}

	public function banHost( $regex )
	{
		exec('varnishadm req.http.host ~ "' . $regex . '"');
	}
}
