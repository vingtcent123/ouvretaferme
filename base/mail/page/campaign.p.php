<?php
new \mail\CampaignPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \mail\Campaign([
			'farm' => $data->eFarm
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCommunicationsMailing($data->eFarm).'?success=mail:Campaign::created');
	});

new \mail\CampaignPage()
	->doDelete(fn() => throw new ReloadAction());
?>
