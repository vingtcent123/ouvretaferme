<?php
new Page()
	->get('document', function($data) {

		$data->eHistory = \association\HistoryLib::getById(GET('id'))->validate('canReadDocument');

		if($data->eHistory['document'] === NULL) {

			$data->eHistory = \association\HistoryLib::getForDocument(GET('id'));
			$content = \association\HistoryLib::generateDocument($data->eHistory);

		} else {

			$content = \association\HistoryLib::getPdfContent($data->eHistory['document']);
		}

		if($content === NULL) {
			throw new NotExistsAction();
		}

		$filename = new \association\AssociationUi()->getDocumentFilename($data->eHistory).'.pdf';

		throw new PdfAction($content, $filename);

	});

new Page()
	->remote('index', 'selling', function($data) {

		$data->eHistory = \association\HistoryLib::getForDocument(GET('id'));

		if($data->eHistory->empty()) {
			throw new NotExpectedAction('Cannot generate PDF of unknown association history');
		}

		$data->eFarmOtf = \farm\FarmLib::getById(Setting::get('association\farm'));

		throw new ViewAction($data);

	});
