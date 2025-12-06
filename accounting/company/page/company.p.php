<?php
new \farm\FarmPage()
	->applyElement(function($data, \farm\Farm $e) {
		$e->validate('canManage');
	})
	->update(function($data) {

		// Get Partner data
		if(FEATURE_DROPBOX) {
			$data->partners = ['dropbox' => \account\DropboxLib::getPartnerData($data->eFarm)];
		}

		throw new ViewAction($data);

	});

?>
