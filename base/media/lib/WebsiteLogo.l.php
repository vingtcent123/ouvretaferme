<?php
namespace media;

class WebsiteLogoLib extends MediaLib {

	public function buildElement(): \website\Website {

		$eWebsite = POST('id', 'website\Website');

		if(
			$eWebsite->empty() or
			\website\Website::model()
				->select('logo', 'farm')
				->get($eWebsite) === FALSE
		) {
			throw new \NotExistsAction('Website');
		}

		// L'utilisateur n'est pas le propriétaire de la ferme
		if($eWebsite->canWrite() === FALSE) {

			// L'utilisateur n'est pas non plus admin
			if(\website\WebsiteSetting::getPrivilege('admin') === FALSE) {
				throw new \NotAllowedAction();
			}

		}

		return $eWebsite;

	}

}
?>
