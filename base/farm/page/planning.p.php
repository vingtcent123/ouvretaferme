<?php
new \farm\FarmPage(function($data) {

		$data->week = GET('week', fn($value) => Filter::check('?week', $value));

	})
	->remote('getWeeklyPdf', 'selling', function($data) {

		$data->cAction = \farm\ActionLib::getByFarm($data->e, index: 'id');
		\farm\ActionLib::saveMain($data->cAction);

		\series\RepeatLib::createForWeek($data->e, $data->week);

		$data->ccTask = \series\TaskLib::getForExport($data->e, $data->week, $data->cAction);

		throw new ViewAction($data);

	})
	->read('downloadWeeklyPdf', function($data) {

		$filename = 'Planning '.$data->week.'.pdf';
		$content = \selling\PdfLib::build('/farm/planning:getWeeklyPdf?id='.$data->e['id'].'&week='.$data->week, $filename);

		throw new PdfAction($content, $filename);

	}, validate: ['canPlanning']);
?>
