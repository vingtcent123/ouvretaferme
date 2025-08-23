<?php
new \farm\FarmPage()
	->applyElement(function($data, \farm\Farm $e) {
		$e->validate('canManage');
	})
	->update(function($data) {

		// Get Partner data
		$data->partners = ['dropbox' => \account\DropboxLib::getPartnerData($data->eFarm)];

		throw new ViewAction($data);

	});

?>
