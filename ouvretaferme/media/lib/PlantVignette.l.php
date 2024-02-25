<?php
namespace media;

class PlantVignetteLib extends MediaLib {

	public function buildElement(): \plant\Plant {

		$ePlant = POST('id', 'plant\Plant');

		if(
			$ePlant->empty() or
			\plant\Plant::model()
				->select(['vignette', 'farm', 'fqn'])
				->get($ePlant) === FALSE
		) {
			throw new \NotExistsAction('Plant');
		}

		if($ePlant->isOwner() === FALSE) {
			throw new \NotAllowedAction();
		}

		return $ePlant;

	}

}
?>
