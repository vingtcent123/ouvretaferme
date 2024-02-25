<?php
namespace media;

class WebsiteFaviconLib extends MediaLib {

	public function buildElement(): \website\Website {

		$eWebsite = POST('id', 'website\Website');

		if(
			$eWebsite->empty() or
			\website\Website::model()
				->select('favicon', 'farm')
				->get($eWebsite) === FALSE
		) {
			throw new \NotExistsAction('Website');
		}

		// L'utilisateur n'est pas le propriétaire de la ferme
		if($eWebsite->canWrite() === FALSE) {

			// L'utilisateur n'est pas non plus admin
			if(\Privilege::can('website\admin') === FALSE) {
				throw new \NotAllowedAction();
			}

		}

		return $eWebsite;

	}

}
?>
