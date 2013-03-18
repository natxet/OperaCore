<?php

namespace OperaCore;

class Varnish
{
    const VARNISHADM = 'varnishadm -S /etc/varnish/secret.www-data -T 127.0.0.1:6082';

	public function banUrl( $regex )
	{
		exec( Varnish::VARNISHADM . ' ban req.url ~ "' . $regex . '"');
	}

	public function banHost( $regex )
	{
		exec( Varnish::VARNISHADM . ' ban req.http.host ~ "' . $regex . '"');
	}
}
