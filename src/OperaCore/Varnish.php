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
		exec('varnishadm ban req.http.host ~ "' . $regex . '"');
	}
}