<?php

namespace OperaCore\Model;
/**
 * User: nacho
 * Date: 01/02/12
 */
class GeoIP extends \OperaCore\Model
{
	const TEST_IP       = '81.36.161.13';
	const LOCALHOST_IP  = '127.0.0.1';

// TODO: Convert this to a vendor
	public function getCity( $ip )
	{

		if( self::LOCALHOST_IP == $ip ) $ip = self::TEST_IP;

		return $this->getCityFromIPnum( $ip );
	}

	/**
	 * @param $ip string String representation of the IP address
	 * @return mixed
	 */
	protected function getCityFromIPnum( $ip )
	{
		$sql = <<<QUERY
SELECT
	l.country,
	l.region,
	l.city
FROM
	geoip.location l
JOIN
	geoip.blocks b
	ON ( l.locId = b.locId )
WHERE
	b.endIpNum >= INET_ATON( :ip )
ORDER BY b.endIpNum
LIMIT 1
QUERY;
		$params = array( ':ip' => $ip );
		return $this->fetchOne( $sql, $params );
	}
}
