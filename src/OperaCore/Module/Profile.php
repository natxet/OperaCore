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
		$this->context = array(
			'checkpoints' => \OperaCore\Profile::$checkpoints,
			'models_profile' => $models,
			'exception_profile' => \OperaCore\Profile::$collections['Exception'],
			'templates_profile' => \OperaCore\Profile::$collections['Templates'],
			'routes_profile' => \OperaCore\Profile::$collections['Route'],
			'slow_query_miliseconds' => 300
		);
		$this->render( 'profile.html.twig' );
	}
}
