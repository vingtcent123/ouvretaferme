<?php
namespace shop;

class PdfLib {

	public static function buildSales(Date $eDate): string {

		return \selling\PdfLib::build('/shop/date:getSales?id='.$eDate['id']);

	}

}
?>
