<?php
namespace analyze;

class Cultivation extends CultivationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'series' => ['name', 'mode'],
			'grossMargin' => new \Sql('CAST(turnover AS SIGNED) - CAST(costs AS SIGNED)', 'int'),
			'turnoverByArea' => new \Sql('IF(area > 0, turnover / area, NULL)', 'float'),
			'grossMarginByArea' => new \Sql('IF(area > 0, (CAST(turnover AS SIGNED) - CAST(costs AS SIGNED)) / area, NULL)', 'float'),
			'grossMarginByWorkingTime' => new \Sql('IF(workingTime > 0, (CAST(turnover AS SIGNED) - CAST(costs AS SIGNED)) / workingTime, NULL)', 'float'),
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);

		return $this['farm']->canAnalyze();

	}

}
?>