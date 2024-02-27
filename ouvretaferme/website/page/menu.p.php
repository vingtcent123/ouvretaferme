<?php
(new \website\MenuPage(function($data) {
		$data->eWebsite = \website\WebsiteLib::getById(INPUT('website'));
	}))
	->create(function($data) {

		$data->cWebpage = \website\WebpageLib::getByWebsiteForMenu($data->eWebsite);
		$data->for = GET('for', ['webpage', 'url'], fn() => throw new NotExpectedAction('For'));

		throw new ViewAction($data);

	})
	->getCreateElement(function($data) {
		return new \website\Menu([
			'website' => $data->eWebsite,
			'farm' => $data->eWebsite['farm']
		]);
	})
	->doCreate(function($data) {
		throw new BackAction('website', 'Menu::created');
	});

(new \website\MenuPage())
	->applyElement(function($data, \website\Menu $e) {

		$e['website'] = \website\WebsiteLib::getById($e['website']);

		$e->validate('canWrite');

	})
	->quick(['label'])
	->doDelete(function($data) {
		throw new ReloadAction('website', 'Menu::deleted');
	});

(new \website\WebsitePage())
	->write('doUpdatePositions', function($data) {
		\website\MenuLib::updatePositions($data->e, explode(',', POST('positions')));
		throw new ReloadAction();
	});
?>
