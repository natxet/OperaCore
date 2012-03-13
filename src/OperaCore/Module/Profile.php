<?php
namespace OperaCore\Module;
/**
 * User: nacho
 * Date: 03/02/12
 */
class Profile extends \OperaCore\Controller
{

	public function actionShow()
	{
		$models = isset(\OperaCore\Profile::$collections['Models']) ? \OperaCore\Profile::$collections['Models'] : array();

		$exception_profile = isset(\OperaCore\Profile::$collections['Exception'])
			? \OperaCore\Profile::$collections['Exception'] : array();

		$this->context = array(
			'checkpoints' => \OperaCore\Profile::$checkpoints,
			'models_profile' => $models,
			'exception_profile' => $exception_profile,
			'templates_profile' => \OperaCore\Profile::$collections['Templates'],
			'routes_profile' => \OperaCore\Profile::$collections['Route'],
			'slow_query_miliseconds' => 300
		);
		$this->render( 'profile.html.twig' );
	}
}
