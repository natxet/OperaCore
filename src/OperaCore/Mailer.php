<?php
namespace OperaCore;
/**
 * User: nacho
 * Date: 09/02/12
 */

class Mailer
{
	/**
	 * @$gateways array of Swift object
	 */
	protected $transports = array();

	/**
	 * @var array with defaults config
	 */
	protected $defaults;

	/**
	 * @var array with defaults config
	 */
	protected $transports_config;

	public function __construct( $c )
	{
		require_once( VENDOR_PATH . 'swiftmailer/swiftmailer/lib/swift_required.php' );
		$this->defaults          = $c['email_defaults'];
		$this->transports_config = $c['smtp_gateways'];

	}

	public $failures = array();

	/**
	 * @param string $transport_name the transport name
	 */
	protected function initTransport( $transport_name )
	{
		if( 'sendmail' == $transport_name ) {

			$transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
		}
		else {

			$config    = $this->transports_config[$transport_name];
			$transport = \Swift_SmtpTransport::newInstance(
				$config['hostname'], $config['port'], $config['security']
			)->setUsername( $config['username'] )->setPassword( $config['password'] );
		}
		$this->transports[$transport_name] = \Swift_Mailer::newInstance( $transport );
	}

	/**
	 * @param string $transport_name
	 *
	 * @return \Swift_Mailer
	 */
	protected function getTransport( $transport_name )
	{
		if ( !array_key_exists( $transport_name, $this->transports ) )
		{
			$this->initTransport( $transport_name );
		}
		return $this->transports[$transport_name];
	}

	/**
	 * @param array $params array with some of this keys: from, to, html, txt, subject
	 * @param string $transport_name the gateway to send through
	 * @return int number of recipients
	 */
	public function send( $params, $transport_name = 'sendmail' )
	{
		$params    = $this->checkParams( $params );
		$transport = $this->getTransport( $transport_name );

		$message = \Swift_Message::newInstance()->setSubject( $params['subject'] )->setFrom( $params['from'] )->setTo(
			$params['to']
		);

		if ( isset( $params['html'] ) ) $message->setBody( $params['html'], 'text/html' );
		if ( isset( $params['text'] ) ) $message->addPart( $params['text'], 'text/plain' );
		//$message->attach(Swift_Attachment::fromPath('my-document.pdf'))

		return ( $recipients = $transport->send( $message, $this->failures ) );
	}

	/**
	 * @param array $params The params
	 * @return array The params changed if needed
	 * @throws \UnexpectedValueException
	 */
	protected function checkParams( $params )
	{
		if ( !isset( $params['to'] ) || !is_array( $params['to'] ) || !count( $params['to'] ) )
		{
			throw new \UnexpectedValueException( 'Mailer: no address in TO' );
		}

		if ( !isset( $params['text'] ) && !isset( $params['html'] ) )
		{
			throw new \UnexpectedValueException( 'Mailer: no content to send (try html and/or text)' );
		}

		if ( !isset( $params['subject'] ) )
		{
			throw new \UnexpectedValueException( 'Mailer: no subject' );
		}

		$params['from'] = ( isset( $params['from'] ) && is_array( $params['from'] ) && count(
			$params['from']
		) ) ? $params['from'] : array( $this->defaults['noreply'] => $this->defaults['noreply_name'] );

		return $params;
	}
}
