<?php
namespace analyze;

class ProductLib extends ProductCrud {

	public static function getByReport(Report $eReport, mixed $index = ['product', NULL]): \Collection {

		return Product::model()
			->select(Product::getSelection())
			->whereReport($eReport)
			->where('turnover > 0')
			->sort(['turnover' => SORT_DESC])
			->getCollection(NULL, NULL, $index);

	}

}
?>
