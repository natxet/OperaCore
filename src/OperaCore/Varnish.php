<?php

namespace OperaCore;

class Varnish
{

	public function flushRegex( $regex )
	{
		exec('varnishadm ban.url "' . $regex . '"');
	}
}
