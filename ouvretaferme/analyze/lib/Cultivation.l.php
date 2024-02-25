<?php
namespace analyze;

class CultivationLib extends CultivationCrud {

	public static function getByReport(Report $eReport, mixed $index = NULL): \Collection {

		return Cultivation::model()
			->select(Cultivation::getSelection())
			->whereReport($eReport)
			->sort('id')
			->getCollection(index: $index);

	}

	public static function update(Cultivation $e, array $properties): void {

		Cultivation::model()->beginTransaction();

		parent::update($e, $properties);

		if(array_intersect($properties, ['area', 'workingTime', 'costs', 'turnover'])) {
			ReportLib::recalculate($e['report']);
		}

		Cultivation::model()->commit();

	}

}
?>
