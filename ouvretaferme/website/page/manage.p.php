<?php
(new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canCommunication');


	}))
	->get('index', function($data) {

		$data->eWebsite = \website\WebsiteLib::getByFarm($data->eFarm);

		if($data->eWebsite->notEmpty()) {
			$data->cWebpage = \website\WebpageLib::getByWebsite($data->eWebsite);
			$data->cMenu = \website\MenuLib::getByWebsite($data->eWebsite, onlyActive: FALSE);
			$data->cNews = \website\NewsLib::getByWebsite($data->eWebsite, onlyPublished: FALSE);
			$data->eWebsite['cDesign'] = \website\DesignLib::getAll();
		}

		throw new ViewAction($data);

	});

new \website\WebsitePage()
	->getCreateElement(function($data) {

		$eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \website\Website([
			'farm' => $eFarm,
		]);

	})
	->doCreate(fn($data) => throw new ReloadAction('website', 'Website::created'));

new \website\WebsitePage()
	->update()
	->read('contact', fn($data) => throw new ViewAction($data))
	->read('newsletter', fn($data) => throw new ViewAction($data))
	->doUpdate(fn($data) => throw new BackAction('website', 'Website::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction())
	->doUpdateProperties('doUpdateDomainTry', ['domainTry'], fn() => throw new ReloadAction('website', 'Website::domainRetry'))
	->doUpdateProperties('doCustomize', ['customDesign', 'customWidth', 'customText', 'customBackground', 'customColor', 'customLinkColor', 'customFont', 'customTitleFont'], fn() => throw new ReloadAction('website', 'Website::customized'))
	->doDelete(fn($data) => throw new ReloadAction('website', 'Website::deleted'));
?>
