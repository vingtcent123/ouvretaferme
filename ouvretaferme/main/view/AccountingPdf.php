<?php
/**
 * Template pour PDF
 */
class AccountingPdfTemplate extends \PdfTemplate {

	public function __construct() {

		\Asset::css('util', 'font-open-sans.css');
		\Asset::css('main', 'design.css');
		\Asset::css('main', 'pdf.css');

	}

}

?>
