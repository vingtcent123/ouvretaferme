<?php
namespace media;

class FarmLogoLib extends MediaLib {

	public function buildElement(): \farm\Farm {

		$eFarm = POST('id', 'farm\Farm');

		if(
			$eFarm->empty() or
			\farm\Farm::model()
				->select('logo')
				->get($eFarm) === FALSE
		) {
			throw new \NotExistsAction('Farm');
		}

		// L'utilisateur n'est pas le propriétaire de la ferme
		if($eFarm->canManage() === FALSE) {

			// L'utilisateur n'est pas non plus admin
			if(\farm\FarmSetting::getPrivilege('admin') === FALSE) {
				throw new \NotAllowedAction();
			}

		}

		return $eFarm;

	}

}
?>
