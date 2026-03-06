<?php
new Page()
	->get('index', function($data) {

		$data->cArchive = \cash\ArchiveLib::getList();

		throw new ViewAction($data);

	});

new \cash\ArchivePage()
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlConnected().'/cash/archives?success=cash\\Archive::created'))
	->read('get', function($data) {

		throw new DataAction($data->e['csv'], Action::CSV, 'archive-'.$data->e['id'].'.csv');

	});
?>
