<?php
new \selling\ArchivePage()
	->getCreateElement(function($data) {

		return new \selling\Archive([
			'farm' => \farm\FarmLib::getById(INPUT('farm')),
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlSellingSales($data->e['farm'], \farm\Farmer::ARCHIVES).'?success=selling\\Archive::created'))
	->read('get', function($data) {

		throw new DataAction($data->e['csv'], Action::CSV, 'archive-'.$data->e['id'].'.csv');

	});
?>
