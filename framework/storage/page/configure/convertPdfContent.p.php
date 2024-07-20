<?php
/**
 * Add a new size for an image
 *
 */
(new Page())
	->cli('index', function($data) {


		$cPdfContent = \selling\PdfContent::model()
			->select(\selling\PdfContent::getSelection())
			->whereHash(NULL)
			->getCollection();

		foreach($cPdfContent as $ePdfContent) {

			$hash = NULL;
			(new \media\PdfContentLib())->send($ePdfContent, $hash, $ePdfContent['binary'], 'pdf');

			echo '.';

		}


	});

?>
