<?php
(new \website\NewsPage(function($data) {

		$data->eWebsite = \website\WebsiteLib::getById(INPUT('website'));

	}))
	->create(function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->eWebsite['farm']);

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);
	})
	->getCreateElement(function($data) {
		return new \website\News([
			'website' => $data->eWebsite,
			'farm' => $data->eWebsite['farm'],
			'publishedAt' => date('Y-m-d H:00:00')
		]);
	})
	->doCreate(function($data) {
		throw new RedirectAction('/website/manage?id='.$data->eWebsite['farm']['id'].'&success=website:News::created');
	});

(new \website\NewsPage())
	->applyElement(function($data, \website\News $e) {

		$e['website'] = \website\WebsiteLib::getById($e['website']);

		$e->validate('canWrite');

	})
	->quick(['title', 'publishedAt'])
	->update(function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);
	})
	->doUpdate(function($data) {
		throw new BackAction('website', 'News::updated');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction())
	->doDelete(function($data) {
		throw new ReloadAction('website', 'News::deleted');
	});
?>
