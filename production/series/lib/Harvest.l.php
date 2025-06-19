<?php
namespace series;

class HarvestLib extends HarvestCrud {

	public static function getElementFromTask(Task $eTask, string $date, float $quantity): \Element {

		$e = new Harvest([
			'farm' => $eTask['farm'],
			'task' => $eTask,
			'series' => $eTask['series'],
			'cultivation' => $eTask['cultivation'],
			'unit' => $eTask['harvestUnit'],
			'quantity' => $quantity,
			'date' => $date,
			'week' => toWeek($date),
		]);

		return $e;

	}

	public static function getTasksByWeek(\farm\Farm $eFarm, string $week): \Collection {

		$firstDate = week_date_starts($week);
		$lastDate = week_date_ends($week);

		return Harvest::model()
			->whereFarm($eFarm)
			->whereDate('BETWEEN', new \Sql(Harvest::model()->format($firstDate).' AND '.Harvest::model()->format($lastDate)))
			->group('task')
			->getColumn('task');

	}

}
?>
