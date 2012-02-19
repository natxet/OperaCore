<?php

namespace natxet\OperaCore\Model;
/**
 * User: nacho
 * Date: 01/02/12
 */
class GeoIP extends \natxet\OperaCore\Model
{
	const TEST_IP       = '81.36.161.13';
	const LOCALHOST_IP  = '127.0.0.1';

// TODO: Convert this to a vendor
	public function getCity( $ip )
	{

		if( self::LOCALHOST_IP == $ip ) $ip = self::TEST_IP;

		$ipnum = $this->convertIPtoNumber( $ip );

		return $this->getCityFromIPnum( $ipnum );
	}

	/**
	 * @param $ip string The IP in string format
	 *
	 * @return int The IP transformed into integer
	 * @throws \InvalidArgumentException
	 */
	protected function convertIPtoNumber( $ip )
	{
		if ( !preg_match( '/(?:\d{1,3}\.){3}\d{1,3}/', $ip ) )
		{
			throw new \InvalidArgumentException( "$ip is not an IP" );
		}

		list( $block1, $block2, $block3, $block4 ) = explode( '.', $ip );
		return ( 16777216 * $block1 ) + ( 65536 * $block2 ) + ( 256 * $block3 ) + $block4;
	}

	protected function getCityFromIPnum( $ipnum )
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
	b.endIpNum >= :ipnum
ORDER BY b.endIpNum
LIMIT 1
QUERY;
		$params = array( ':ipnum' => $ipnum );
		return $this->fetchOne( $sql, $params );
	}
}
