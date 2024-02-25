<?php
(new \website\NewsPage(function($data) {
		$data->eWebsite = \website\WebsiteLib::getById(INPUT('website'));
	}))
	->create(fn($data) => throw new ViewAction($data))
	->getCreateElement(function($data) {
		return new \website\News([
			'website' => $data->eWebsite,
			'farm' => $data->eWebsite['farm']
		]);
	})
	->doCreate(function($data) {
		throw new BackAction('website', 'News::created');
	});

(new \website\NewsPage())
	->applyElement(function($data, \website\News $e) {

		$e['website'] = \website\WebsiteLib::getById($e['website']);

		$e->validate('canWrite');

	})
	->quick(['title', 'publishedAt'])
	->update()
	->doUpdate(function($data) {
		throw new BackAction('website', 'News::updated');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction())
	->doDelete(function($data) {
		throw new ReloadAction('website', 'News::deleted');
	});
?>
