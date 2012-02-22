<?php
namespace OperaCore\Module;
/**
 * User: nacho
 * Date: 03/02/12
 */
class Profile extends \OperaCore\Controller
{

	protected function actionShow()
	{
		$context = array(
			'checkpoints' => \OperaCore\Profile::$checkpoints,
			'models_profile' => \OperaCore\Profile::$collections['Models'],
			'templates_profile' => \OperaCore\Profile::$collections['Templates'],
			'slow_query_miliseconds' => 300
		);
		$this->render( 'profile.html.twig', $context );
	}
}
