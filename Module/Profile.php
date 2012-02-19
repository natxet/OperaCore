<?php
namespace natxet\OperaCore\Module;
/**
 * User: nacho
 * Date: 03/02/12
 */
class Profile extends \natxet\OperaCore\Controller
{

	protected function actionShow()
	{
		$context = array(
			'checkpoints' => \natxet\OperaCore\Profile::$checkpoints,
			'models_profile' => \natxet\OperaCore\Profile::$collections['Models'],
			'templates_profile' => \natxet\OperaCore\Profile::$collections['Templates'],
			'slow_query_miliseconds' => 300
		);
		$this->render( 'profile.html.twig', $context );
	}
}
