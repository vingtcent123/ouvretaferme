<?php
(new \website\WebpagePage(function($data) {
		$data->eWebsite = \website\WebsiteLib::getById(INPUT('website'));
	}))
	->create(fn($data) => throw new ViewAction($data))
	->getCreateElement(function($data) {

		return new \website\Webpage([
			'website' => $data->eWebsite,
			'farm' => $data->eWebsite['farm'],
			'template' => \website\TemplateLib::getByFqn('open')
		]);

	})
	->doCreate(function($data) {
		throw new BackAction('website', 'Webpage::created');
	});

(new \website\WebpagePage())
	->applyElement(function($data, \website\Webpage $e) {

		$e['website'] = \website\WebsiteLib::getById($e['website']);

		$e->validate('canWrite');

	})
	->update()
	->doUpdate(function($data) {
		throw new BackAction('website', 'Webpage::updated');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction())
	->read('updateContent', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['website']['farm'])->validate('canManage');

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);

	})
	->doUpdateProperties('doUpdateContent', ['content'], fn($data) => throw new ViewAction($data))
	->doDelete(function($data) {
		throw new ReloadAction('website', 'Webpage::deleted');
	});
?>
